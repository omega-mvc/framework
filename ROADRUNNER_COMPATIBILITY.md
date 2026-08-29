# RoadRunner Compatibility Audit — Omega Framework

**Audit date:** 2026-08-28
**Scope:** `src/` of the `omega-mvc/framework` repository (435 PHP source files)
**Target runtime:** RoadRunner (RR) PHP Application Server — a **persistent-worker** model where a single PHP process is kept alive and executes **many sequential HTTP requests** without re-bootstrapping the interpreter.

This document is both an **architectural audit** and an **actionable remediation guide**. It maps the framework's state management, identifies every construct that violates RoadRunner's persistent-worker assumptions, and prescribes precise code-level fixes.

---

## Table of Contents

1. [RoadRunner Model vs. Standard FPM Model](#1-roadrunner-model-vs-standard-fpm-model)
2. [Codebase Discovery: Architecture Map](#2-codebase-discovery-architecture-map)
3. [Verdict](#3-verdict)
4. [Severity-Classified Findings](#4-severity-classified-findings)
5. [Detailed Findings & Remediation](#5-detailed-findings--remediation)
   - [5.1 Container instance-store accumulates forever](#51-container-instance-store-accumulates-forever)
   - [5.2 `Application::$app` static singleton is never reset per request](#52-applicationapp-static-singleton-is-never-reset-per-request)
   - [5.3 Router static route registry & request-time state](#53-router-static-route-registry--request-time-state)
   - [5.4 Facade static instance cache](#54-facade-static-instance-cache)
   - [5.5 Event dispatcher listener accumulation](#55-event-dispatcher-listener-accumulation)
   - [5.6 Database: broken reconnection + log/transaction leakage](#56-database-broken-reconnection--logtransaction-leakage)
   - [5.7 View/Templator singleton accumulation](#57-viewtemplator-singleton-accumulation)
   - [5.8 In-memory cache growth](#58-in-memory-cache-growth)
   - [5.9 Macroable & AppServiceProvider static registries](#59-macroable--appserviceprovider-static-registries)
   - [5.10 Boot-Time-Only Registration Contract](#510-boot-time-only-registration-contract)
6. [Compatibility Assessment Matrix](#6-compatibility-assessment-matrix)
7. [The RoadRunner Worker Adapter](#7-the-roadrunner-worker-adapter)
8. [Per-Request Reset Contract (RFC)](#8-per-request-reset-contract)
9. [Verified Code Locations Index](#9-verified-code-locations-index)
10. [Priority-Ordered Remediation Roadmap](#10-priority-ordered-remediation-roadmap)

---

## 1. RoadRunner Model vs. Standard FPM Model

| Concern | PHP-FPM / `php -S` | RoadRunner (persistent worker) |
|---|---|---|
| Process lifetime | One process per request (reaped after). | One process serves **many** requests. |
| Class/static state | Re-initialized each request — safe by construction. | **Persists across requests** — a leak/stale-data hazard unless explicitly reset. |
| Superglobals (`$_SERVER` etc.) | Fresh per request from the SAPI. | Must be **re-injected manually** by the worker; RR does not populate them automatically. |
| Container singletons | Fresh each request. | Shared for process lifetime; must not hold **request-scoped** data. |
| DB connections | Created/closed per request. | **Must be pooled and reused**; closing per request kills performance. |
| `exit`/`die`/`header()`/ob | Allowed (request ends). | **Forbidden** — kills the worker or corrupts the response. |
| `register_shutdown_function`, error/exception handlers | Per request. | Re-registrations accumulate; handlers leak state. |

---

## 2. Codebase Discovery: Architecture Map

Omega bootstraps in the following layered fashion (verified against source):

```
Request (fresh per cycle)
   │
   ▼
Omega\Http\Http::handle(Request)                 [src/Omega/Http/Http.php:126]
   │  $this->app->set('request', $request)       // replaces shared 'request' instance each cycle — GOOD
   ▼
bootstrap() → Application::bootstrapWith()       [AbstractApplication.php:198]
   ├─ ConfigBootstrapper
   ├─ HandleExceptions                              // re-registers error/shutdown handlers EVERY request
   ├─ FacadeBootstrapper
   ├─ RegisterProviders                             // guarded by loadedProviders[]
   └─ BootProviders                                 // guarded by isBooted
   ▼
dispatcher() → middlewarePipeline() → route dispatching → response
   ▼
Http::terminate(Request, Response)               [Http.php:181]
   ├─ per-middleware terminate()
   └─ Application::terminate() → registered terminateCallbacks
```

**Key lifecycle facts (verified):**
- **`Http::handle()` re-binds `'request'` on every cycle** (`Http.php:128` + `Container::set` at `Container.php:135`), so the classic "shared request singleton reused across requests" bug is **correctly avoided** in the default flow. This is the framework's best native trait for RR.
- **`Http::terminate()` is NOT a state reset.** It only invokes registered terminal callbacks (`AbstractApplication::terminate`, `AbstractApplication.php:344`) and middleware `terminate()` methods. It does **not** call `flush()`, `Router::reset()`, `flushInstance()`, `clearListeners()`, or any per-request cleanup.
- **No per-request `flush()`/`reset()` is invoked anywhere in `src/`.** The only code path that calls `Application::flush()` is the **test suite** (`src/Omega/Testing/TestCase.php`) and `setBaseBinding()` when a *new* `Application` is constructed.
- Therefore, **as shipped, the framework is not reproducible between requests within a single worker** unless the RoadRunner adapter explicitly resets state (Section 7 & 8).

**State-focus subsystems and their core state holders:**

| Subsystem | Central type(s) | State holder (persistence across requests in a worker) |
|---|---|---|
| Application | `AbstractApplication` | `static Application $app` + `Container::$instances/$bindings` + provider/callback arrays |
| Container/DI | `Container`, `Resolver`, `ReflectionCache` | `$instances` (grows), `$bindings`, `ReflectionCache` (instance, safe) |
| Router | `AbstractRouter`, `Router`, `RouteDispatcher` | `static $routes/$current/$group/$patterns/...` |
| Facades | `AbstractFacade` | `static $app`, `static $instance[]` |
| Events | `Dispatcher` | instance `$listeners[]` on a shared singleton |
| Database | `DatabaseManager`, `AbstractConnection`, `Model` | `$connections[]`, `$pdo`, `$logs[]` (grows), static `Model::$dispatcher` |
| View | `Templator`, `TemplatorFinder`, `Vite`, `DirectiveTemplator` | `Templator::$dependency` (grows), static directive/cache arrays |
| Cache | `CacheManager`, `Storage\Memory` | `Memory::$storage` (grows without bound) |
| Macro/Provider | `MacroableTrait`, `AppServiceProviderTrait` | `static $macros`, `static $modules` |

---

## 3. Verdict

### Overall rating: **NOT RoadRunner-compatible out of the box — but remediable with moderate, well-scoped changes.**

**Strengths already aligned with RR:**
- Per-request `Request` rebinding (`Http::handle` → `Container::set`).
- DB connections created once and *not* closed per query — natural pooling foundation.
- Service provider registration/boot is idempotent (guarded by `isBooted`/`loadedProviders`).
- `ReflectionCache` is instance-scoped and stores **immutable** class metadata — it is safe to persist and in fact *beneficial* under RR (avoids re-reflection).
- Explicit `terminate()`/`flush()`/`reset()` primitives exist, ready to be orchestrated.

**Fatal/blocking incompatibilities that must be fixed:**
1. **No per-request reset orchestration** — static singleton, container instance store, router static routes, facade instance cache, dispatcher listeners, templator dependencies, and in-memory cache all persist/accumulate across every request in a long-lived worker.
2. **DB reconnection is broken** (`AbstractConnection.php:106` calls a non-existent `isLostConnection()` method) and there is **no runtime reconnect** after a mid-worker drop — a dead PDO handle kills the worker permanently.
3. **`AbstractConnection::$logs` grows unboundedly** across requests (no auto-flush at request boundary).
4. **DB transactions can leak across requests** (begin/commit on a shared PDO with no request-end rollback guarantee).
5. **Superglobals reliance** — `Router::run()` reads `$_SERVER['REQUEST_URI']`/`$_SERVER['REQUEST_METHOD']` directly (`Router.php:267`); the RR worker must re-seed `$_SERVER` or the router must accept request-derived input.

The remainder of this document details every finding and its precise remediation.

---

## 4. Severity-Classified Findings

| ID | Severity | Finding | Location |
|---|---|---|---|
| F-01 | **CRITICAL** | Container `$instances` store grows & persists across requests; shared singletons hold request-scoped data | `Container.php:56-153`, `resolve()` |
| F-02 | **CRITICAL** | `Application::$app` static singleton never reset per request; helpers/`app()` read stale global state | `AbstractApplication.php:57,129,283`; `Application/helper.php:60` |
| F-03 | **CRITICAL** | DB reconnection broken (calls missing `isLostConnection`) + no runtime reconnect | `AbstractConnection.php:96-112,117` |
| F-04 | **CRITICAL** | No per-request state reset orchestration anywhere in request pathway | `Http.php:126-191` |
| F-05 | **HIGH** | DB `$logs[]` accumulates unboundedly across requests | `AbstractConnection.php:197-205,238` |
| F-06 | **HIGH** | DB transaction state leaks across requests on shared PDO | `AbstractConnection.php:312-331` |
| F-07 | **HIGH** | `Router` static `$routes/$current/$group` persist/mutate per request; `$_SERVER` read directly | `AbstractRouter.php:23-72`; `Router.php:267-280` |
| F-08 | **HIGH** | View `Templator::$dependency` grows on every render of the shared singleton | `Templator.php:83,135,180-196,311` |
| F-09 | **HIGH** | Cache `Memory::$storage` grows unboundedly; TTL'd-but-unread keys never GC'd | `Cache/Storage/Memory.php:65,86-141` |
| F-10 | **MEDIUM** | Event `Dispatcher::$listeners` accumulates if listeners registered during a request | `Dispatcher.php:61` |
| F-11 | **MEDIUM** | Facade static `$instance[]` cache can return stale instances across app resets | `Facade/AbstractFacade.php:45-48,105-112` |
| F-12 | **MEDIUM** | `HandleExceptions` re-registers error/shutdown handlers every request | `Exceptions/Bootstrapper/HandleExceptions.php:96-108` |
| F-13 | **LOW** | `MacroableTrait::$macros`, `AppServiceProviderTrait::$modules` statics grow | `MacroableTrait.php:38`; `AppServiceProviderTrait.php:26` |
| F-14 | **LOW** | `Vite::$cache/$hot`, `DirectiveTemplator::$directive`, `TemplatorFinder::$views` persistent statics | `Vite.php:79,82`; `DirectiveTemplator.php:48`; `TemplatorFinder.php:45` |

---

## 5. Detailed Findings & Remediation

### 5.1 Container instance-store accumulates forever

**Evidence — `src/Omega/Container/Container.php`:**
```php
// lines 56-62
protected array $bindings = [];
protected array $instances = [];          // <-- shared singleton cache
protected array $aliases = [];
```

```php
// lines 135-153 — set(): any non-Closure is stored as a resolved shared instance
public function set(string $name, mixed $value): void
{
    if ($value instanceof Closure) {
        $this->bind($name, $value, true);  // closure => shared factory
        return;
    }
    $name = $this->getAlias($name);
    $this->instances[$name] = $value;      // <-- persists for process lifetime
    $this->bindings[$name]  = [
        'concrete' => fn () => $this->instances[$name],
        'shared'   => true,
    ];
}
```
`resolve()` returns and caches shared instances into `$this->instances` (lines ~440-462). `flush()` (`Container.php:324-333`) clears all of it, **but is never called automatically between requests**.

**Why it breaks RR:** Every service resolved once (DB manager, view templator, dispatcher, cache manager, config, etc.) lives for the whole worker. If application code stores **request-scoped** data in the container via `set('foo', $requestScopedValue)`, that value and anything it references is retained until the worker restarts — a stale-data leak and a memory growth vector. Worse, because `set()` **always** overrides the previous binding, repeated `set('request', ...)` is fine (each cycle replaces), but any *accumulating* container key (arrays appended by `set` with the same key) is a leak.

**Remediation:**
- Introduce an explicit **request-scoped binding marker** or simply **do not** store request-scoped values in the shared container. Use constructor/parameter injection instead.
- Add a `resetForRequest()` method to the Container that clears only the request-scoped subset while preserving long-lived infrastructure (see Section 8 for the RFC). At minimum, ensure the RR adapter calls `$app->flush()` at the end of each request if the app supports reconstruction.
- Alternatively, adopt the **reconstruct-per-request** strategy: construct a fresh `Application` per request (Section 7, Strategy B), which is safe but pays re-reflection/bootstrap cost per request.

**Code example — a request-scoped reset that preserves the app:**
```php
// src/Omega/Container/Container.php
/** Keys that must be torn down after every request in a persistent worker. */
protected array $requestScopedKeys = [];

public function setRequestScoped(string $name, mixed $value): void
{
    $this->set($name, $value);
    $this->requestScopedKeys[$this->getAlias($name)] = true;
}

public function resetRequestScope(): void
{
    foreach (array_keys($this->requestScopedKeys) as $name) {
        unset($this->instances[$name], $this->bindings[$name]);
    }
    $this->requestScopedKeys = [];
}
```

---

### 5.2 `Application::$app` static singleton is never reset per request

**Evidence — `src/Omega/Application/AbstractApplication.php`:**
```php
protected static ?Application $app = null;      // line 57

public static function getInstance(): ?Application  // line 129
{
    return Application::$app;
}

protected function setBaseBinding(): void           // lines 167-187
{
    if (Application::$app !== null) {
        Application::$app->flush();                 // only reset path = new Application constructed
    }
    Application::$app = $this;
    // ...
}
```

**Evidence — `src/Omega/Application/helper.php:60`:** global `app()` returns `Application::getInstance()`, so every helper (`get_path()`, `app()`, `is_dev()`, etc.) reaches the process-global singleton.

**Why it breaks RR:** The static singleton persists for the worker's lifetime. All global helpers and all code that calls `app()` resolve against the *same* long-lived instance. Unless the RR adapter flushes/reconstructs, any per-request mutations to that instance accumulate.

**Remediation:**
- Implement a per-request reset that **detaches** the singleton without requiring a full reconstruct: add `Application::resetForRequest()` that calls the container's request-scope reset (5.1) and clears request-time callbacks (`terminateCallback`, any request-time `bootingCallback`/`bootedCallback` additions) while leaving core bindings intact.
- Document that application code must **never** store request-scoped data on the `app()` singleton.
- Update `flush()` to also reset `AbstractFacade::flushInstance()` and `Router::reset()` (see 5.3/5.4) so the whole logical reset is one call.

**Code example:**
```php
// AbstractApplication.php — add a dedicated request teardown, called by the worker adapter
public function resetForRequest(): void
{
    $this->terminateCallback = [];
    $this->resetRequestScope();            // Container::resetRequestScope() from 5.1
    Router::reset();                       // see 5.3
    AbstractFacade::flushInstance();
    Env::reloadIfRequested();
    // per-request log flush, transaction rollback — see 5.6
}
```

---

### 5.3 Router static route registry & request-time state

**Evidence — `src/Omega/Router/AbstractRouter.php`:**
```php
protected static array $routes = [];                  // line 23
protected static ?Route $current = null;              // line 30
protected static $pathNotFound;                       // line 39
protected static $methodNotAllowed;                   // line 49
public static array $group = ['prefix' => '', 'middleware' => []];  // line 60
public static array $patterns = [...];                // line 72
```

**Evidence — `src/Omega/Router/Router.php`:**
```php
// line 267 — reads superglobals directly
$dispatcher = RouteDispatcher::dispatchFrom($_SERVER['REQUEST_URI'], $_SERVER['REQUEST_METHOD'], self::$routes);
// line 280 — overwrites static "current route" each request
self::$current = $dispatcher->current();
```

**Analysis:**
- `$routes` is registered **once** at boot (guarded by `isBooted`), so a persistent route table is actually *desirable* and not a leak by itself.
- `$current` is overwritten each `run()` — self-healing **provided `run()` executes every request**. If `run()` is skipped or `$current` is read outside the cycle, stale route data leaks.
- `$group` (prefix/middleware) is mutated via `RouteGroup` setup/cleanup closures; an exception inside a group leaves `$group` polluted, leaking prefixes/middleware into subsequent requests.
- **`Router::reset()` exists (`AbstractRouter.php:113`) but is never called in `src/`.** It clears `$routes`, `$pathNotFound`, `$methodNotAllowed`, `$group` — but does **not** clear `$current` or `$patterns` (a gap worth fixing).
- **`$_SERVER` dependency** is an RR integration point: RR does not populate `$_SERVER` for you.

**Remediation:**
1. **Route registration once, never during requests** — enforce that all routes are defined at boot (they already are, via the guarded provider). Document that dynamic per-request route registration is forbidden under RR.
2. **Extend `Router::reset()`** to also reset `$current`, `$patterns`, and `$macro` state:
   ```php
   // AbstractRouter.php
   public static function reset(): void
   {
       static::$routes         = [];
       static::$current        = null;   // add
       static::$pathNotFound   = null;
       static::$methodNotAllowed = null;
       static::$group          = ['prefix' => '', 'middleware' => []];
       // optionally reset static::$patterns to its default instead of clearing
   }
   ```
3. **Remove the `$_SERVER` dependency** in `Router::run()`. Accept the URI/method from the `Request` object or as method parameters, and have the RR adapter pass them explicitly:
   ```php
   public static function run(string $uri, string $method): mixed
   {
       $uri    = $uri    ?: ($_SERVER['REQUEST_URI']    ?? '/'); // fallback for non-RR
       $method = $method ?: ($_SERVER['REQUEST_METHOD'] ?? 'GET');
       $dispatcher = RouteDispatcher::dispatchFrom($uri, $method, self::$routes);
       self::$current = $dispatcher->current();
       // ...
   }
   ```
   This keeps back-compat while giving the worker a clean path to inject request data.

---

### 5.4 Facade static instance cache

**Evidence — `src/Omega/Facade/AbstractFacade.php`:**
```php
protected static ?ApplicationInterface $app = null;      // line 45
protected static array $instance = [];                   // line 48

protected static function getFacadeBase(string $name): mixed  // lines 105-112
{
    if (array_key_exists($name, static::$instance)) {
        return static::$instance[$name];
    }
    return static::$instance[$name] = static::$app->make($name);
}

public static function flushInstance(): void            // lines 119-122
{
    static::$instance = [];
    static::$app = null;
}
```

**Analysis:** `$instance` is bounded by the number of distinct accessor names (e.g. `DB`, `PDO`, `Schema`, `Cache`, `Hash`, `View`, `Vite`) — it does **not** grow per request, so it is not an unbounded leak. However, after `Application::flush()` + a new application construction, a stale cached instance from the *old* container could be returned (the `array_key_exists` guard returns the stale entry). This becomes a correctness bug in the reconstruct-per-request strategy.

**Remediation:** Ensure `flushInstance()` is invoked as part of the per-request reset (it is already part of the logical reset in 5.2's `resetForRequest()`). Add a `FacadeBootstrapper`-style re-injection of `AbstractFacade::$app` after any application reconstruction.

---

### 5.5 Event dispatcher listener accumulation

**Evidence — `src/Omega/Event/Dispatcher/Dispatcher.php:61`:**
```php
protected array $listeners = [];
```
The `Dispatcher` is registered in the container as a **shared singleton** (`EventServiceProvider.php:44-63`) and is also wired into the **static** `Model::$dispatcher` (`Model.php:108,924-950`).

**Analysis:** If any listener/subscriber is registered **during** a request (route handler, model boot, user code), it remains in the shared singleton's `$listeners` array for the worker's lifetime — a memory leak **and** a stale-behavior leak (it fires on subsequent requests where it should not). `clearListeners()`/`removeListener()`/`removeSubscriber()` exist but are never called between requests.

**Remediation:** Enforce all listener registration at **boot time only**. If dynamic registration is unavoidable, add a per-request teardown that captures the set of listeners at request start and restores it at request end:
```php
// in the worker adapter, per request:
$snapshot = $dispatcher->getListeners();      // if such accessor exists
// ... handle request ...
$dispatcher->clearListeners();                // or restore($snapshot)
```
Prefer the simplest rule: **register listeners in service providers (boot), never in request handlers.**

---

### 5.6 Database: broken reconnection + log/transaction leakage

#### 5.6.1 Broken/missing reconnection (CRITICAL) — `src/Omega/Database/AbstractConnection.php`

```php
// lines 96-112 — createPdo() retries on "lost connection"
protected function createPdo(...): PDO
{
    try {
        return new PDO($dsn, $username ?? '', $password ?? '', $options);
    } catch (PDOException $e) {
        if ($this->isLostConnection($e)) {           // line 106  <-- BUG: method does not exist
            return new PDO($dsn, $username ?? '', $password ?? '', $options);
        }
        throw $e;
    }
}

// lines 117-169 — the actual method is named:
protected function causedByLostConnection(Throwable $e): bool
```

`createPdo()` is only invoked from the constructor. The retry (a) calls a **non-existent method** `isLostConnection` — throwing an `Error` inside the `catch`, masking the original exception — and (b) even if renamed, only guards the *initial* connect. **There is no runtime reconnect**: `$this->pdo` is a `protected PDO` set once in the constructor and never replaced. In a long-lived worker, once the DB drops, every subsequent query fails against the dead cached handle; the connection is never rebuilt.

**Remediation — fix the name and add runtime reconnect:**
```php
// AbstractConnection.php
protected function createPdo(...): PDO
{
    try {
        return new PDO($dsn, $username ?? '', $password ?? '', $options);
    } catch (PDOException $e) {
        if ($this->causedByLostConnection($e)) {     // rename call site to match method
            return new PDO($dsn, $username ?? '', $password ?? '', $options);
        }
        throw $e;
    }
}

/**
 * Reconnect if the persisted connection has gone away.
 * Call this before every query in a persistent worker.
 */
protected function reconnectIfLost(): void
{
    if (null === $this->pdo) {
        return;
    }
    try {
        $this->pdo->query('SELECT 1');
    } catch (\Throwable $e) {
        if (!$this->causedByLostConnection($e)) {
            throw $e;
        }
        $this->pdo = $this->createPdo(
            $this->buildDsn(),
            $this->configs['username'] ?? null,
            $this->configs['password'] ?? null,
            $this->options()
        );
    }
}
```
Invoke `reconnectIfLost()` **only at the start of `beginTransaction()`**. Invoking it also from `query()`/`execute()` is unsafe: the `SELECT 1` ping runs on the single shared PDO handle and, during nested subqueries (an outer fetch still holding an open result set while an inner query runs), MySQL rejects the concurrent ping with `SQLSTATE[HY000] 2014: Cannot execute queries while there are pending result sets`. Keeping the reconnect to the transactional boundary removes that regression while still transparently rebuilding a dropped link. (A future enhancement could add lazy retry-on-failure around `execute()`, re-preparing the statement against the rebuilt PDO.) This is the single most important DB fix for RR.

*(Note: `PDO::ATTR_PERSISTENT => true` is already set at `AbstractConnection.php:22`, which engages PHP's persistent-connection cache — good — but it does **not** provide cross-request *reattempt* logic on the PHP side; the reconnect wrapper above is still required.)*

#### 5.6.2 Unbounded query-log accumulation (HIGH) — `AbstractConnection.php`

```php
protected array $logs = [];                    // line ~34
public function execute(): bool                // lines 238-246
{
    $start = microtime(true);
    $execute = $this->statement->execute();
    $this->addLog($this->query, $start, microtime(true));   // appends to $this->logs
    return $execute;
}
// addLog at lines 197-205 does: $this->logs[] = [...];
```
`$logs` grows with one entry per query and is **never auto-flushed between requests** (`flushLogs()` exists at `AbstractConnection.php:336` but is manual). In a worker handling many requests, this is an unbounded memory leak.

**Remediation:** Call `flushLogs()` at the end of each request (in the reset orchestration), or bound the array. Preferred: auto-flush at request boundary:
```php
// in resetForRequest() / worker adapter per request:
$connection->flushLogs();
```
Consider also a size cap (e.g. keep the last N entries) if `flushLogs()` is not reliably called.

#### 5.6.3 Transaction state leakage (HIGH) — `AbstractConnection.php:312-331`

`beginTransaction()`/`endTransaction()`/`cancelTransaction()` operate directly on the shared PDO. If a request begins a transaction but returns without commit/rollback, the open transaction **leaks into the next request** on the same worker connection.

**Remediation:** Guarantee rollback at the request boundary:
```php
// in resetForRequest() of the worker adapter:
if ($connection->inTransaction()) {
    $connection->cancelTransaction();   // rollback any dangling transaction
}
```
Ensure `inTransaction()` reflects `$this->pdo->inTransaction()`.

#### 5.6.4 Model static state — `Model.php:107-108`

`protected static ?DispatcherInterface $dispatcher = null;` is a single non-accumulating reference (safe). No static model registry, no static connection holder. **No remediation required.**

#### 5.6.5 DatabaseManager pooling — `DatabaseManager.php:47-102`

`$connections[]` caches one `ConnectionInterface` per name and reuses it; resolvers are bounded. `clearConnections()` exists for manual reset. **No change needed** — this is the correct pooling shape.

**Connection-state note:** `$statement` and `$query` (last prepared statement / last query) are overwritten each query — safe in a single-request-at-a-time worker.

---

### 5.7 View/Templator singleton accumulation

**Evidence — `src/Omega/View/Templator.php`:**
```php
private array $dependency = [];              // line 83
public function addDependency(string $parent, string $child, int $dependDeep = 1): self  // line 135
{
    $this->dependency[$parent][$child] = $dependDeep;
}
```
`Templator` is a **shared singleton** (`ViewServiceProvider.php:85-88`, bound via closure → shared). During `render()` it calls `prependDependency()` (line ~311), so `$this->dependency` grows with every new template/combo rendered across all requests — **unbounded growth + stale dependency data**.

**Other persistent view statics (bounded but global):**
- `TemplatorFinder::$views` (`TemplatorFinder.php:45`) — bounded per-view-name cache; `flush()` exists.
- `DirectiveTemplator::$directive` (`DirectiveTemplator.php:48`) — grows if dynamic directives registered.
- `Vite::$cache` / `Vite::$hot` (`Vite.php:79,82`) — persistent; `flush()` exists.
- Per-pass statics in `SectionTemplator`/`ComponentTemplator`/`IncludeTemplator` (`self::$cache = []` reset at start of `parse()`) — effectively scoped to one compile pass, **safe**.

**Remediation:**
1. **Bound or flush `Templator::$dependency` per request** — add a `clearDependencies()` and call it in the reset orchestration, or convert it to a fixed-capacity LRU.
   ```php
   public function clearDependencies(): void
   {
       $this->dependency = [];
   }
   ```
2. **Bind `Templator` as shared but request-safe** — ensure `render()` does not mutate cross-request state (it already rebuilds compilation per call; only the `$dependency` cache persists).
3. For `Vite`/`DirectiveTemplator`, call `Vite::flush()` and (if dynamic) reset the directive registry in the per-request reset, or enforce static/directive registration only at boot.

---

### 5.8 In-memory cache growth

**Evidence — `src/Omega/Cache/Storage/Memory.php:65`:**
```php
protected array $storage = [];
```
`Memory` is bound as a **shared singleton** (`CacheServiceProvider.php:75-97` via closure→shared). The class docblock claims it "does not persist data between requests" (`Memory.php:30-31`) — **factually wrong under RR**: as a singleton in a persistent worker it persists across requests. TTL expiry is only checked on `get()` (`Memory.php:94-100`); keys written with TTL but never read are **never GC'd**, so `$storage` grows without bound. If the app stores *request-scoped* tokens/users in the memory driver, both a memory leak and cross-request data bleed occur.

**Bonus bug:** `Memory::setMultiple()` returns `false` unconditionally (`Memory.php:146-153`).

**Remediation:**
- **Do not default to the in-memory driver** in production workers. Default to `File`, `Redis`, or `Apcu` (those don't hold growing PHP arrays; Apcu/Redis are shared externally).
- If the memory driver is used, add a **size cap / eviction policy** and enforce TTL GC on write as well as read.
- Fix `setMultiple()` to return `true` on success:
   ```php
   public function setMultiple(iterable $values, int|\DateInterval|null $ttl = null): bool
   {
       foreach ($values as $key => $value) {
           $this->set($key, $value, $ttl);
       }
       return true;   // was: return false;
   }
   ```

---

### 5.9 Macroable & AppServiceProvider static registries

**Evidence:**
```php
// src/Omega/Support/MacroableTrait.php:38
protected static array $macros = [];
// src/Omega/Container/AppServiceProviderTrait.php:26
protected static array $modules = [];
```
`$macros` grows with every `macro()` registration; `$modules` grows with every `export()`. Neither is auto-reset (`resetMacro()`/`flushModule()` exist but are manual).

**Remediation:** Register macros and export modules **only at boot**; document that per-request dynamic macro/module registration is forbidden. Optionally include `resetMacro()`/`flushModule()` in the per-request reset if dynamic registration is required (avoid this if possible).

---

### 5.10 Boot-Time-Only Registration Contract

**Status:** Applied as *documentation* (Phase 3, point 9). No dev-mode assertion was added: the four registries below (`Dispatcher` listeners, `Macroable` macros, `AppServiceProvider` modules, `DirectiveTemplator` directives) are standalone classes that are not container-aware, so a runtime assertion would require injecting `Application` boot state into each of them — rejected as invasive and fragile.

**The rule (project-wide, mandatory for persistent workers):** every registry below must be populated **only during the boot phase** — i.e. inside `ServiceProvider::register()` or `ServiceProvider::boot()`, or in your application's `bootstrap/app.php` route/directive setup — and **never inside a request handler, middleware, controller or `Http::handle()` path**. Rationale: the framework's shared singletons for these registries persist for the entire worker process; any entry added during one request stays in memory and keeps firing/being visible on every subsequent request in that worker (memory leak **and** stale-behavior leak).

| Registry | Location | Grows via | Reset primitive |
|---|---|---|---|
| Event listeners | `Dispatcher::addListener()` — `src/Omega/Event/Dispatcher/Dispatcher.php:66` | `addListener()`, `addSubscriber()` | `clearListeners()`, `removeListener()`, `removeSubscriber()` |
| Macros | `MacroableTrait::macro()` — `src/Omega/Macroable/MacroableTrait.php:47` | `macro()` | `resetMacro()` |
| Modules | `AppServiceProviderTrait::export()` — `src/Omega/Container/AppServiceProviderTrait.php:118` | `export()` | `flushModule()` |
| View directives | `DirectiveTemplator::register()` — `src/Omega/View/Templator/DirectiveTemplator.php:81` | `register()` | none (avoid dynamic use) |

**Guidance:**
- **Listeners:** register them in a service provider's `boot()` (e.g. `$this->app->get('events')->addListener(...)` or `DispatcherInterface::class`), never in a route handler or model boot callback that can fire per request.
- **Macros / modules / directives:** register at application bootstrap time only. If an application genuinely needs dynamic per-request registration, it must maintain its own snapshot/restore around `handle()` — but this is strongly discouraged.
- **Enforcement in tests (recommended):** assert in your integration tests that the listener/macro/module/directive counts are identical before and after a `handle()` round-trip. This turns the documented contract into an automated regression guard without touching the standalone registries.
- Under a RoadRunner worker, prefer the anatomy already wired in Phase 1/2: mark genuinely per-request bindings request-scoped (`Container::setRequestScoped()`) and run `Application::resetForRequest()` (Section 8) at the end of each request; the boot-time-only rule above applies to the *global* registries that `resetForRequest()` intentionally does **not** clear.

---

## 6. Compatibility Assessment Matrix

| Area | RR-compatible as-is? | Action required |
|---|---|---|
| Per-request Request object rebinding (`Http::handle`) | ✅ Yes | None (keep) |
| DB connection pooling / no per-query connect | ✅ Yes | None (keep) |
| PDO `ATTR_PERSISTENT` | ✅ Yes | None (keep) |
| ReflectionCache (instance, immutable metadata) | ✅ Yes | None (keep) — beneficial under RR |
| Idempotent provider registration/boot | ✅ Yes | None (keep) |
| **Per-request state reset** | ❌ No | **Add reset orchestration (Section 7/8)** |
| **Container `$instances` accumulation** | ❌ No | Request-scoped binding + reset (5.1) |
| **`Application::$app` static singleton** | ❌ No | Per-request detach (5.2) |
| **Router static route/group state + `$_SERVER`** | ❌ No | Reset + inject URI/method (5.3) |
| **Facade static cache** | ⚠️ Partial | `flushInstance()` in reset (5.4) |
| **Event listener accumulation** | ⚠️ Partial | Boot-only registration / reset (5.5) |
| **DB reconnect** | ❌ No (broken) | Fix method name + reconnect wrapper (5.6.1) |
| **DB `$logs` accumulation** | ❌ No | Flush per request (5.6.2) |
| **DB transaction leakage** | ❌ No | Rollback per request boundary (5.6.3) |
| **Templator `$dependency` growth** | ❌ No | Flush/bound per request (5.7) |
| **Memory cache growth** | ❌ No | Non-memory default + cap + GC (5.8) |
| **Macro/module statics** | ⚠️ Partial | Boot-only registration (5.9) |

---

## 7. The RoadRunner Worker Adapter

To run Omega under RoadRunner you need a `psr/container`-compatible RR worker entrypoint (the `spiral/roadrunner-http` + `spiral/roadrunner-worker` packages) that:

1. Boots the Omega application **once** at worker start.
2. For each incoming RR request: builds a fresh `Request`, calls `kernel->handle()`, writes the `Response` to RR's `StreamWriterInterface`, then runs the per-request teardown (Section 8).

**Suggested worker skeleton (`worker.php`):**
```php
<?php
declare(strict_types=1);

use Spiral\RoadRunner\Worker;
use Spiral\RoadRunner\Http\PSR7Worker;
use Spiral\RoadRunner\Environment;

require __DIR__ . '/vendor/autoload.php';

$worker  = Worker::create();
$psr7    = new PSR7Worker($worker, new \Nyholm\Psr7\Factory\Psr17Factory(), new \Nyholm\Psr7\Factory\Psr17Factory(), new \Nyholm\Psr7\Factory\Psr17Factory());
$app     = require __DIR__ . '/bootstrap/app.php';      // constructs Application once
$kernel  = $app->make(\Omega\Http\Http::class);          // resolve kernel once

while (true) {
    $request = $psr7->waitRequest();
    if (null === $request) {
        break;
    }
    try {
        // 1. Populate superglobals from RR request (Router still reads $_SERVER) — see 5.3.
        $_SERVER['REQUEST_URI']    = $request->getUri()->getPath();
        $_SERVER['REQUEST_METHOD'] = $request->getMethod();
        // optionally $_COOKIE/$_GET/$_POST/$_FILES/$_SERVER['REMOTE_ADDR'] etc.

        // 2. Map PSR-7 -> Omega Request, or use an Omega PSR-7 bridge.
        $omegaRequest = /* convert $request to \Omega\Http\Request */;

        // 3. Handle.
        $response = $kernel->handle($omegaRequest);
        $psr7->respond(/* convert $response to PSR-7 */);
    } catch (\Throwable $e) {
        $psr7->getWorker()->error((string) $e);
    } finally {
        // 4. Run the per-request reset (Section 8).
        resetForRequest($app);
    }
}
```

> **Important:** Because Omega's `Router::run()` reads `$_SERVER` directly, the worker must re-seed `$_SERVER` (as shown) **or** the router method must be refactored per 5.3 to accept the request. The `$_SERVER` seeding is a stopgap; the refactor is the durable fix.

---

## 8. Per-Request Reset Contract (RFC)

The single most important deliverable of this audit is a **defined, guaranteed per-request teardown**. Every RoadRunner integration must run this after each request. Photograph the exact top-to-bottom sequence:

```php
/**
 * Per-request teardown for a persistent RoadRunner worker.
 * MUST run after EVERY completed request, in this order.
 *
 * @param  \Omega\Application\Application $app the long-lived Application singleton
 * @return void
 */
function resetForRequest(Omega\Application\Application $app): void
{
    // 1. Terminate middleware + registered terminal callbacks (existing behaviour).
    // $kernel->terminate($request, $response);   // run BEFORE teardown, if not already

    // 2. Flush DB query logs to stop unbounded growth (5.6.2).
    foreach ($app->get(DatabaseManager::class)->getConnections() as $connection) {
        $connection->flushLogs();
    }

    // 3. Roll back any dangling active transaction (5.6.3).
    foreach ($app->get(DatabaseManager::class)->getConnections() as $connection) {
        if ($connection->inTransaction()) {
            $connection->cancelTransaction();
        }
    }

    // 4. Reset the container's request-scoped bindings (5.1).
    $app->resetRequestScope();

    // 5. Reset Router static request-time state (5.3) and Facade cache (5.4).
    Router::reset();
    AbstractFacade::flushInstance();

    // 6. Clear view/templator dependency cache (5.7) if any accumulated.
    $app->get('view.instance')->clearDependencies();

    // 7. (Optional, only if used) bound the in-memory cache or switch default (5.8).
    // $app->get('cache')->clear();   // only if Memory driver and data is request-scoped

    // 8. Release the request object so nothing holds it across cycles.
    $app->set('request', null);   // optional: avoids retaining the last request
}
```

**Guarantee ordering rationale:**
- Terminate callbacks may touch the DB, so they run **first** (before logs are flushed / transactions rolled back — actually, run terminate **before** step 2).
- Container and facade resets must come after any application code that resolves services during teardown.
- Router/facade/view resets are independent of the DB resets and order among them only matters for cleanliness.

**Also required in the worker:**
- Re-seed `$_SERVER` and other superglobals per request **before** `handle()` (5.3), until the router refactor lands.
- Do **not** call `exit`/`die`, do not rely on `register_shutdown_function` for per-request work (it accumulates — see below).

---

### Fix `HandleExceptions` re-registration (F-12) — `Exceptions/Bootstrapper/HandleExceptions.php:96-108`

`HandleExceptions::bootstrap()` runs every request and calls `set_error_handler`/`set_exception_handler`/`register_shutdown_function` each time. Over many requests these registrations stack. While functionally idempotent, they leak handler closures referencing the singleton. Guard them:
```php
// HandleExceptions.php
private bool $handlersRegistered = false;

public function bootstrap(\Omega\Application\ApplicationInterface $app): void
{
    if ($this->handlersRegistered) {
        return;
    }
    // ... existing registration ...
    $this->handlersRegistered = true;
}
```

---

## 9. Verified Code Locations Index

| File | Lines | Subject |
|---|---|---|
| `src/Omega/Container/Container.php` | 56-62, 135-153, 440-462, 306-333 | `$instances`/`$bindings` persistence; `set()` shared capture; `flush()` |
| `src/Omega/Application/AbstractApplication.php` | 57, 129-132, 167-187, 213-234, 283-295, 300-318, 344-353 | static `$app`; singleton/`setBaseBinding`; idempotent boot/register; `flush()`; `terminate()` |
| `src/Omega/Application/helper.php` | 60-68, 94-105 | `app()`/`get_path()` hit global singleton |
| `src/Omega/Application/Application.php` | 155-173 | alias registration (`request`, `config`, ...) |
| `src/Omega/Router/AbstractRouter.php` | 23, 30, 39, 49, 60, 72, 84-122, 216-281 | static routes/current/group/patterns; `reset()` |
| `src/Omega/Router/Router.php` | 267, 280 | `$_SERVER` read; `self::$current` overwrite |
| `src/Omega/Facade/AbstractFacade.php` | 45-48, 105-112, 119-122 | static app/instance cache; `flushInstance()` |
| `src/Omega/Event/Dispatcher/Dispatcher.php` | 61 | `$listeners` accumulation |
| `src/Omega/Event/EventServiceProvider.php` | 44-63 | dispatcher singleton + dual instances |
| `src/Omega/Database/DatabaseManager.php` | 47-48, 71-74, 86-102 | `$connections` pooling; `clearConnections()` |
| `src/Omega/Database/AbstractConnection.php` | 15-24, 48-60, 96-169, 179-205, 238-246, 312-339 | PDO lifetime; broken reconnect; `$logs`; transactions |
| `src/Omega/Database/Model/Model.php` | 107-108, 924-950 | static dispatcher (safe) |
| `src/Omega/View/Templator.php` | 83, 135, 180-196, 311 | `$dependency` growth |
| `src/Omega/View/ViewServiceProvider.php` | 84-88 | `Templator`/`TemplatorFinder` singletons |
| `src/Omega/View/TemplatorFinder.php` | 45, 78-85, 149 | `$views` cache |
| `src/Omega/View/Vite.php` | 79, 82, 244-248 | static `$cache`/`$hot`; `flush()` |
| `src/Omega/View/Templator/DirectiveTemplator.php` | 48, 81-87 | static `$directive` |
| `src/Omega/Cache/Storage/Memory.php` | 30-31, 65, 86-141, 146-153 | growable `$storage`; wrong `setMultiple()` |
| `src/Omega/Cache/CacheServiceProvider.php` | 75-97 | cache manager/driver singletons |
| `src/Omega/Support/MacroableTrait.php` | 38, 47-50, 114-117 | static `$macros` |
| `src/Omega/Container/AppServiceProviderTrait.php` | 26, 118-145 | static `$modules` |
| `src/Omega/Http/Http.php` | 96-102, 126-147, 162-165, 181-191, 244-247 | bootstrappers; per-request `'request'` set; terminate (no reset) |
| `src/Omega/Exceptions/Bootstrapper/HandleExceptions.php` | 96-108 | handler re-registration per request |

---

## 10. Priority-Ordered Remediation Roadmap

**Phase 1 — Blocking blockers (must fix before any RR deployment):**
1. Fix DB reconnect: rename `isLostConnection` → `causedByLostConnection` (`AbstractConnection.php:106`) **and** add a runtime `reconnectIfLost()` invoked at the `beginTransaction()` boundary (kept out of `query()`/`execute()` to avoid `2014 pending result sets` on nested subqueries) (5.6.1).
2. Implement the **per-request reset orchestration** (Section 8) in the worker adapter — this alone neutralizes F-01/F-02/F-04/F-05/F-06/F-07/F-08/F-10.
3. Remove/decouple the `$_SERVER` dependency in `Router::run()` (5.3) or guarantee superglobal reseeding in the worker.

**Phase 2 — Memory safety hardening:**
4. Flush DB `$logs` and roll back dangling transactions per request (5.6.2/5.6.3) — fold into the reset.
5. Bound or flush `Templator::$dependency` (5.7); default cache to File/Redis/Apcu and cap/GC the Memory driver (5.8); fix `setMultiple()` (5.8).
6. Guard `HandleExceptions` handler registration (F-12).

**Phase 3 — Correctness & hygiene:**
7. ~~Add `resetRequestScope()` to the Container and `resetForRequest()` to `AbstractApplication` as first-class framework APIs (5.1/5.2) so future apps and RR adapters share one contract.~~ **APPLIED** — `Container::setRequestScoped()`/`resetRequestScope()` (`Container.php:350,366`) and `AbstractApplication::resetForRequest()` (`AbstractApplication.php:374`) are now public first-class APIs; `Http::terminate()` calls `$this->resetForRequest()` after `$app->terminate()`.
8. ~~Extend `Router::reset()` to clear `$current`/`$patterns` (5.3).~~ **APPLIED** — `AbstractRouter::reset()` now clears `self::$current = null` and restores `self::$patterns` to the default set (`AbstractRouter.php:120`).
9. ~~Enforce boot-time-only listener / macro / module / directive registration via documentation and/or a dev-mode assertion (5.5/5.9).~~ **APPLIED (documentation)** — see §5.10 Boot-Time-Only Registration Contract. Dev-mode assertion intentionally omitted (registries are non-container-aware); enforcement is by documented contract + optional test snapshot assertion.
10. Add an integration test that simulates multiple sequential `handle()` calls on one `Application` and asserts: no cross-request leakage, bounded `$logs`, GC'd memory cache, no dangling transactions, and stable memory footprint. **PENDING — deferred to the test-revision pass.**

---

### Conclusion

Omega's core request dispatch (`Http::handle` replacing `'request'` each cycle), its connection pooling, and its idempotent boot are all natively RoadRunner-aligned. **The decisive gap is the absence of a per-request state-reset contract**: the process-global `Application` singleton, the container's shared instance store, and multiple static registries (Router, Facade, Dispatcher listeners, Templator dependencies, Memory cache) all persist and accumulate across the many requests a single worker handles. Combined with a genuinely broken DB reconnection path and unbound query logs, the framework **cannot run correctly as a long-lived RoadRunner worker without remediation**.

Apply Phase 1 to unblock, Phase 2 to make it memory-safe, and Phase 3 to institutionalize a shared reset contract — the resulting framework is then fully compliant with RoadRunner's persistent-worker model.
