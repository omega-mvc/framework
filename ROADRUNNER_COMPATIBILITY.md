# Omega Framework — RoadRunner (Persistent Worker) Compatibility Audit & Remediation Guide

**Target:** `omega-mvc/framework` v1.0.0 (`Omega\`, PSR-4 → `src/Omega/`)
**Audit objective:** Determine whether the framework can run successfully as a long-lived worker under [RoadRunner](https://roadrunner.dev) (PHP application server), map all state, and enumerate every modification required for full compliance.
**Audit date:** 2026

---

## 1. Executive Summary

The Omega framework is **already unusually well prepared** for the RoadRunner persistent-worker model. The core container, database layer, exception bootstrapping, facades, and templating were clearly designed with "reset at the boundary of every request" in mind. There are no uninitialized-singleton hazards, no fatal global-state pollution in the *bootstrap path*, and the database layer already implements persistent PDO with automatic reconnection.

**However, there is one critical, request-2-and-beyond breaker**: the route table is registered **only once** (guarded by `AbstractApplication::$isBooted`), yet it is **cleared on every request** by `Router::reset()`. This means the **very first request works and every subsequent request 404s** (unless the route cache file is present). This single defect blocks a RoadRunner deployment entirely.

Beyond that blocker, the audit catalogues a set of *secondary* issues that must be addressed for correctness, hygiene and robustness in a multi-request process:

1. **Route re-registration across requests** (CRITICAL — see §3.1).
2. **`$_SERVER`/superglobal reliance** in the request lifecycle (`Router::run()`, `RequestFactory`, `Request::isSecured()`) which RoadRunner does not populate identically to PHP-FPM (§3.2).
3. **Per-request re-evaluation of configuration files** via `ConfigBootstrapper` on every `handle()` (§3.3 — wasteful, not incorrect).
4. **Static/accumulating memory caches** that hold references for the process lifetime: `Vite::$cache`/`$hot`, `ComponentTemplator::$cache`, `IncludeTemplator::$cache`, `SectionTemplator::$cache`, `DirectiveTemplator::$directive`, `MacroableTrait::$macros`, `Model::$dispatcher`, `Env::$values`, `Router::$routes` (§3.4).
5. **Request-scoping gaps**: several container bindings that are conceptually per-request (e.g. the active `request`, cache manager, view instances) are **not** registered as request-scoped, so they survive `resetRequestScope()` and retain stale state across requests (§3.5).
6. **`isBooted` semantics** are conflated with "routes are loaded"; the once-per-process flag and per-request route loading need to be separated (§3.6).

Everything else — `HandleExceptions` static guard, `AbstractFacade::flushInstance()`, `DatabaseManager::resetConnectionsForRequest()`, `AbstractConnection::reconnectIfLost()` + persistent PDO, `Templator::clearDependencies()`, `Memory` cache GC, `Schedule` pools — is already RoadRunner-correct (§2.3).

---

## 2. Codebase Discovery — Complete State Map

This is the authoritative inventory of **every piece of state** in the framework and how it behaves across consecutive requests in a single worker process.

### 2.1 DI Container (the central state owner)

**File:** `src/Omega/Container/Container.php`

| Property | Kind | Across requests | RoadRunner impact |
|---|---|---|---|
| `$bindings` (`abstract => ['concrete'=>Closure,'shared'=>bool]`) | instance | survives (`flush()` only on full shutdown) | **Good** — factories are process-lifetime by design |
| `$instances` (singleton cache) | instance | survives | **must be cleaned for request-scoped** bindings |
| `$aliases` | instance | survives | Good (static map) |
| `$resolver / $invoker / $reflectionCache / $injector` | instance (lazy) | survives | Good (harmless reflection caches) |
| `$with` (param override stack) | instance | survives | must be emptied (`flush()` does) |
| `$requestScoped` (`array<string,true>`) | instance | survives (the *markers*) | Good — used by `resetRequestScope()` |

**Request-reset contract (already implemented):**
- `Container::setRequestScoped(string $abstract)` (`:350`) marks a binding.
- `Container::resetRequestScope()` (`:366`) unsets `$instances[$abstract]` for every marker, **keeping the factory closures** so a fresh instance is lazily rebuilt next request.
- Docblock explicitly states "essential for RoadRunner-style persistent workers."

**Gap:** Most framework bindings are registered with `$app->set(...)` which forces `shared=true`, and are **never** passed through `setRequestScoped()`. Therefore their singletons survive `resetRequestScope()` unchanged (see §3.5).

### 2.2 The Application container/host

**Files:** `src/Omega/Application/Application.php`, `AbstractApplication.php`

| State | Kind | Reset on request? | RoadRunner impact |
|---|---|---|---|
| `Application::$app` (static app reference) | static | no | **Good** — single app per worker; only nulled by `flush()` |
| `$isBooted` (hooked) | instance | **NO** | **BLOCKS route re-registration (§3.6)** |
| `$isBootstrapped` (hooked) | instance | no | Fine |
| `terminateCallback / bootingCallbacks / bootedCallbacks` | instance | yes (`resetForRequest()` `:376-378`) | Good |
| `providers / loadedProviders / bootedProviders` | instance | no | Good (registered once) |
| Request-scope (`resetRequestScope()`) | — | yes | Good |
| `Router::reset()` (static router) | static | yes (`:382`) | **clears routes → causes bug (§3.1)** |
| `AbstractFacade::flushInstance()` | static | yes (`:384`) | Good |
| `Templator::clearDependencies()` | instance | yes (`:386-391`) | Good — clears per-render deps |

`Application::flush()` resets everything and drops `Application::$app` to `null`; it is the *process-shutdown* (or test-teardown) operation, **not** the per-request reset.

### 2.3 Things that are **already correct** for persistent workers

- **`HandleExceptions`** (`Exceptions/Bootstrapper/HandleExceptions.php`): `private static bool $handlersRegistered` ensures `set_error_handler`/`set_exception_handler`/`register_shutdown_function` run **once per process**, then every subsequent `bootstrap()` is a near-no-op. Correct. Also keeps a 32KB `$reserveMemory` buffer for fatal handling.
- **`AbstractFacade::flushInstance()`**: clears the resolved-facade cache so facades rebuild from the same app next request. Called in both `resetForRequest()` and `flush()`.
- **Database connections** (`Database/AbstractConnection.php` + `DatabaseManager.php`):
  - `PDO::ATTR_PERSISTENT => true` default; `beginTransaction()` calls `reconnectIfLost()` which pings and rebuilds a dead PDO `(SELECT 1)` with a comprehensive lost-connection detection list; `inTransaction()` + `cancelTransaction()` + `flushLogs()` made available for boundary rollback.
  - `DatabaseManager::resetConnectionsForRequest()` (`:95-106`) rolls back dangling transactions and flushes query logs for every cached connection at the request boundary.
- **`Templator::clearDependencies()`** dirties the per-render dependency tracker so views don't accumulate cross-request entries.
- **`Memory` cache storage** (`Cache/Storage/Memory.php`): `$maxItems` + `evictLeastRecentlyWritten()`/`gc()` protect against unbounded growth.
- **`Schedule`** pools are per-instance and bounded by `flush()`; cron is not re-registered per request and is intentionally process-lifetime (§3.6 note).

### 2.4 Static / global state inventory (complete)

| Class *::member* | Static? | Growth across requests? | Reset hook | Verdict |
|---|---|---|---|---|
| `AbstractRouter::$routes` | yes | re-populated per boot only (bug) | cleared in `reset()` | **BUG SOURCE** |
| `AbstractRouter::$group` (prefix/middleware/as) | yes | bounded | cleared in `reset()` (note: `as` reset happens implicitly via whole-array reassignment) | ok-ish |
| `AbstractRouter::$patterns` | yes | bounded | re-seeded in `reset()` | fine (static config) |
| `AbstractRouter::$current / $pathNotFound / $methodNotAllowed` | yes | bounded | cleared | fine |
| `AbstractFacade::$instance` / `$app` | yes | bounded | `flushInstance()` | good |
| `AbstractApplication::$app` | yes | bounded | `flush()` | good |
| `Env::$values` | yes | fixed size | — | good (env is static) |
| `Model::$dispatcher` | yes | fixed | — | good if dispatcher is long-lived (set in `EventServiceProvider`) |
| `HandleExceptions::$handlersRegistered` / `$reserveMemory` | yes | fixed | — | good |
| `DirectiveTemplator::$directive` | yes | **can grow** (app-registered directives) | — | **must be cleared or re-applied** (see §3.4, §2.5) |
| `DirectiveTemplator::$excludeList` | yes | fixed | — | fine |
| `MacroableTrait::$macros` (per-class static) | yes | **can grow** | `resetMacro()` | **must be reset** (used by `Request`, `Response`) |
| `Vite::$cache` / `Vite::$hot` | yes | bounded (manifest keyed) | `flush()` | small; optional flush |
| `ComponentTemplator::$cache` | yes | reset to `[]` at start of every `parse()` | internal | good |
| `IncludeTemplator::$cache` | yes | reset to `[]` at start of every `parse()` | internal | good |
| `SectionTemplator::$cache` | yes | reset to `[]` at start of every `parse()` | internal | good |

> Note: The three templator `$cache` arrays are **already** reset at the start of each `parse()` (e.g. `ComponentTemplator::parse()` `:75`), so they are *not* cross-request leaks. They only hold file contents for the duration of a single render.

### 2.5 Per-request instance state that survives (response / middleware / cookies)

- **`Http\Http::$middlewareUsed`** accumulates the middleware applied per request and is never cleared → grows on every request, and worse, holds references.
- `Dispatcher`/`Event` listeners, `LoggingManager::$driver`, `CacheManager`, `RateLimiter` — all processes-lifetime singletons; generally fine, but see request-scoping notes (§3.5).
- Responses are constructed per request in the kernel; no cross-request reuse. Cookies are per-`Request` instance (safe).
- `Request` uses `MacroableTrait::$macros` (per-class static) — macros registered on one request leak to the next.

---

## 3. RoadRunner Compatibility Assessment

### 3.1 CRITICAL — Routes vanish after the first request (definitive)

**Root-cause chain (verified in source):**

1. **Request 1:** `Http::handle()` → `bootstrap()` → `BootProviders` → `AbstractApplication::bootProvider()` (`AbstractApplication.php:215`). `$isBooted` is `false`, so it iterates core providers and calls `RouteServiceProvider::boot()` (`Router/RouteServiceProvider.php:34`), which registers the route table into **static** `AbstractRouter::$routes`. Then `$isBooted = true` (`:235`).
2. `Router::run()` reads the static route table → request 1 dispatches fine.
3. **End of request 1:** `Http::terminate()` → `Http::resetForRequest()` → `Application::resetForRequest()` (`AbstractApplication.php:374`) → **`Router::reset()` (`AbstractRouter.php:120`) sets `self::$routes = []` and nulls the handlers.**
4. **Request 2:** `Http::handle()` → `bootstrap()` → `BootProviders` → `bootProvider()` **early-returns because `$isBooted` is still `true`** (never reset in `resetForRequest()`). `RouteServiceProvider::boot()` is therefore **never called again** → `AbstractRouter::$routes` stays empty → every request from request 2 onward produces **no matching route** (404 / `pathNotFound`).

**Why this only manifests with 1+ requests:** under classic PHP-FPM each request is a fresh process, so `$isBooted=false` and routes load every time. Under a persistent RoadRunner worker the `isBooted` guard + route-table reset combine to break everything after the first request.

**Affected code:**
```php
// AbstractApplication.php:215
public function bootProvider(): void
{
    if ($this->isBooted) { return; }   // <-- once per process
    ...
    // RouteServiceProvider::boot() runs here ONLY
    $this->isBooted = true;
}
```
```php
// AbstractApplication.php:382 (inside resetForRequest)
Router::reset();   // <-- wipes the routes (and handlers/patterns/group)
```

The **fix must separate "provider boot is done" from "routes need (re)loading per request."** See §4.1.

### 3.2 Superglobal reliance vs RoadRunner PSR-7

RoadRunner hands each request to the worker as a PSR-7 `ServerRequestInterface` (via the goridge relay). It does **not** fully populate PHP's `$_SERVER`, `$_GET`, `$_POST`, `$_COOKIE`, `$_FILES` the way PHP-FPM does, nor does it provide `php://input` in the traditional sense.

The framework reads superglobals in several critical places:

- **`Router::run()`** (`Router/Router.php:258-301`) reads `$_SERVER['REQUEST_URI']` and `$_SERVER['REQUEST_METHOD']` directly.
- **`RequestFactory::getFromGlobal()`** (`Http/RequestFactory.php`) builds the `Request` from `$_SERVER`, `$_GET`, `$_POST`, `$_COOKIE`, `$_FILES`, `php://input`, `REMOTE_ADDR`.
- **`Request::isSecured()`** (`Http/Request.php:497`) reads `$_SERVER['HTTPS']`.

**Assessment:** This is the second-most-important integration point. Even after fixing §3.1, you cannot simply call `Http::handle($request)` from a worker with a PSR-7 request unless the kernel sources its input from the passed object, not the superglobals. RoadRunner provides a full PSR-7 factory precisely so the app never needs `$_SERVER`.

**Fix:** Introduce a `RequestFactory::fromPsr7ServerRequest(Psr\Http\Message\ServerRequestInterface $psr7): Request` path, and have the RoadRunner entry point build the Omega `Request` from the PSR-7 server request. The roadrunner worker then calls `$http->handle($omegaRequest)` and sends `$response` back via `psr7()->respond()` (spiral adapter handles the relay). Details + code in §4.2.

### 3.3 Configuration is re-read on every request (waste, not bug)

`Http::bootstrap()` runs `ConfigBootstrapper` on every `handle()`. `ConfigBootstrapper::loadConfiguration()` (`Config/Bootstrapper/ConfigBootstrapper.php`) **glob/require**`s the config directory or loads the cached `config.php` each time. Idempotent and correct, but on a worker servicing thousands of requests this is pure overhead that should be avoided in production.

**Fix:** cache the assembled `ConfigRepository` once per worker and only rebuild if the cached config file is present / stale. See §4.4.

### 3.4 Static memory-accumulation vectors

Under a long-lived process, statics that **only grow** are memory leaks, and statics carrying **per-request data** are correctness bugs. Reviewed:

- **`DirectiveTemplator::$directive`** (`View/Templator/DirectiveTemplator.php:48`): custom directives registered via `DirectiveTemplator::register()` (e.g. `ViewServiceProvider::registerViteDirectives()` registers `'vite'` at boot). These are intended to be process-lifetime and bounded; the only leak is if application code registers directives per request. Safe to leave, but **must not be registered again on a request boundary** in a way that mutates other per-request state. See §4.5.
- **`MacroableTrait::$macros`** (per-class static, `:38`): if middleware registers macros per request, they persist. Provide a `resetMacro()` call at boundary (§4.5).
- **`Http::$middlewareUsed`** (`Http/Http.php`): grows per request; **must be cleared** in `resetForRequest()`.
- **`Vite::$cache`/`$hot`** (`View/Vite.php:79,82`): bounded, keyed by manifest path, `flush()` available; only relevant in HMR (dev). Optional hygiene clear.
- **`Model::$dispatcher`**: fixed-size static; safe.
- **`Env::$values`**: fixed; safe (env is static config; re-`load()`ing is a no-op with `createImmutable`).
- **`Memory` cache / `LoggingManager::$driver` / `CacheManager`**: already bounded or process-scoped correctly.

### 3.5 Request-scoping gaps in the container

Only bindings marked via `setRequestScoped()` are rebuilt after `resetRequestScope()`. In the framework, many **per-request** services are registered as ordinary shared singletons and thus survive with stale data:

- The active **`request`** binding — `Http::handle()` does `$app->set('request', $request)` each request, but it is *not* marked request-scoped. Because `set()` overwrites `$instances`, this actually does get replaced — **but** any other service that captured the *old* request instance keeps the stale reference.
- The **cache manager / drivers** — process-wide singletons are generally fine, but if the `Memory` driver is used as the app cache, entries written during request N are visible in request N+1 (by design for a cache, but worth noting for rate-limiting/fixed-window where `RateLimiterFactory` uses the cache).
- **View instances / templators** — `view.instance` is a shared singleton; `clearDependencies()` handles the only per-render state, so acceptable.
- Middleware/repository instances that cache request data.

**Fix:** mark at least `request`, per-request `view`/`response` abstractions, and any request-bound repositories as request-scoped so `resetRequestScope()` tears them down. See §4.6.

### 3.6 `isBooted` semantic confusion (tied to §3.1)

`bootProvider()` bundles *all* once-only provider `boot()` logic (DB DSN setup, cache driver registration, cron schedule binding via `CronServiceProvider`, view resolvers, *and* route loading) behind `$isBooted`. The **route loading must happen per request**, but the rest should remain once-per-process. The remediation therefore splits these concerns rather than naively resetting `$isBooted` (which would re-run expensive provider boots every request).

---

## 4. Actionable Remediation Plan

Ordered by priority. Each item includes the precise file, the change, and a code example. Items 4.1 and 4.2 are **mandatory** for a working RoadRunner deployment; 4.3+ are best-practice hardening.

### 4.1 (MANDATORY) Re-register routes on every request

Separate "routes loaded" from "providers booted." Two coordinated changes:

**A. Give the Router a dedicated per-request load path (recommended).**

Add a method on `AbstractApplication` that (re)loads routes without re-booting all providers, and call it *after* `Router::reset()` in `resetForRequest()` (or at the start of `Http::handle()`).

```php
// AbstractApplication.php — new method
final public function loadRoutes(): void
{
    // Ensure route-registration code runs each request, NOT gated by $isBooted.
    // If a route cache exists, RouteServiceProvider::registerRoute() re-adds them;
    // otherwise require the web routes file again (this file must be written to
    // be require'd, not require_once, so it re-executes each request).
}
```

Implement it by adding a `bootRoutes()` hook to the application:

```php
// AbstractApplication.php
public function bootRoutes(): void
{
    $routeProvider = new \Omega\Router\RouteServiceProvider($this);
    $routeProvider->boot();
}
```

But note `RouteServiceProvider::boot()` uses `require_once` for `web.php`/`schedule.php`, so to make it re-execute each request you must switch the *web routes* include to `require` (the route cache path and schedule remain once-only). See the revised provider below.

**B. Modify the boot flow so route loading is per-request and everything else stays once.**

Revised `Http::bootstrap()` / `Http::resetForRequest()`:

```php
// Http/Http.php
public function bootstrap(): void
{
    $this->app->bootstrapWith($this->bootstrappers);
    // BootProviders runs bootProvider() ONCE (guarded by $isBooted).
}

public function resetForRequest(): void
{
    if ($this->app->bound(DatabaseManager::class)) {
        $this->app->get(DatabaseManager::class)->resetConnectionsForRequest();
    }
    if (method_exists($this->app, 'resetForRequest')) {
        $this->app->resetForRequest();   // resets router, facades, request scope
    }
    // NEW: repopulate the route table for the next request.
    if (method_exists($this->app, 'bootRoutes')) {
        $this->app->bootRoutes();
    }
}
```

**Revised `RouteServiceProvider::boot()`** (note `require` for web routes, keep schedule once-only):

```php
// RouteServiceProvider.php
public function boot(): void
{
    // Route cache is process-lifetime (static); load once.
    $routeCache = $this->getApplicationCachePath() . 'route.php';
    if (is_file($routeCache)) {
        $routes = require $routeCache;
        foreach ($routes as $route) {
            $this->registerRoute($route);
        }
        return;
    }

    // Re-register each request so Router::reset() doesn't leave an empty table.
    Router::middleware([MaintenanceMiddleware::class])->group(
        static function (): void {
            require get_path('path.base', 'routes/web.php');   // was require_once
        }
    );

    // Schedule is static config; registering once is fine (guarded by loadedProviders).
    if (!isset($this->scheduleLoaded)) {
        require_once get_path('path.base', 'routes/schedule.php');
        $this->scheduleLoaded = true;
    }
}
```

> If you prefer to avoid touching the provider, an alternative is to **not reset the router** at all and instead (a) register routes exactly once at worker startup and (b) rely on `Router::$routes` persisting; then remove `Router::reset()` from `resetForRequest()`. This is simpler and equally valid since routes are static per worker. **Choose one approach and apply it consistently** — see §4.7 for a worker that preloads routes at startup.

**Verification:** add an integration test that performs two consecutive simulated requests and asserts both dispatch (see §6).

### 4.2 (MANDATORY) Drive the kernel from a PSR-7 request, not superglobals

**A. Add a PSR-7 → Omega `Request` adapter** in `RequestFactory`:

```php
// Http/RequestFactory.php
use Psr\Http\Message\ServerRequestInterface;

public function fromPsr7ServerRequest(ServerRequestInterface $psr7): Request
{
    $query  = new \Omega\Collection\Collection($psr7->getQueryParams());
    $body   = new \Omega\Collection\Collection($psr7->getParsedBody() ?? []);
    $cookies= new \Omega\Collection\Collection($psr7->getCookieParams());
    $files  = new \Omega\Collection\Collection(
        array_map([$this, 'normalizeUpload'], $psr7->getUploadedFiles())
    );
    $headers= new HeaderCollection();
    foreach ($psr7->getHeaders() as $name => $values) {
        $headers->set($name, implode(', ', $values));
    }

    $request = new Request(
        $psr7->getMethod(),
        (string) $psr7->getUri(),
        $query, $body, $headers, $cookies, $files
    );
    $request->initialize(
        remoteAddress: $psr7->getServerParams()['REMOTE_ADDR'] ?? null,
        rawBody: (string) $psr7->getBody(),
    );

    return $request;
}
```

**B. Change `Http::handle()` to use the injected `Request` (already true — it receives `$request`).** The only remaining superglobal reads to remove are in `Router::run()` and `Request::isSecured()`:

```php
// Router.php run(): accept the current URI/method from the matched kernel instead of $_SERVER
public static function run(?string $path = null, ?string $method = null): mixed
{
    $path   ??= /* from $this->app request */;
    $method ??= /* from $this->app request */;
    ...
}
```

Pass the resolved `Request`/URI/method into `Router::run()` from the dispatcher so the router never reads `$_SERVER`.

**C.** `Request::isSecured()` (`Http/Request.php:497`) should fall back to a scheme known from the PSR-7 URI (`$psr7->getUri()->getScheme() === 'https'`) rather than `$_SERVER['HTTPS']`.

### 4.3 (RECOMMENDED) Cache the configuration once per worker

Modify `ConfigBootstrapper::bootstrap()` to build the repository once and keep it, or gate on a worker flag:

```php
// Config/Bootstrapper/ConfigBootstrapper.php
public function bootstrap(ApplicationInterface $app): void
{
    static $cached = null;
    if ($cached === null || !$app->isProduction()) {
        $cached = $this->loadConfiguration($app);          // heavy I/O only once
    }
    $app->loadConfig(new ConfigRepository($cached));
    date_default_timezone_set(env('APP_TIMEZONE') ?? 'UTC');
}
```

In production (which RoadRunner implies) `loadConfig()` becomes a cheap rebind of the same repository. Env and timezone setting remain safe.

### 4.4 (RECOMMENDED) Introduce a RoadRunner worker entry point + `Http` termination contract

Add a worker bootstrap file (e.g. `public/roadrunner-worker.php` or a `Console` command `rr:work`) that:

1. Instantiates the `Application`, binds a fixed base path, builds the `Http` kernel **once**.
2. Registers the `BosunOPs`-style loop via the RoadRunner PSR-7 adapter.
3. For each incoming PSR-7 request: convert via `RequestFactory::fromPsr7ServerRequest`, call `$http->handle()`, return the `Response`.

```php
// public/roadrunner-worker.php
use Spiral\RoadRunner\Worker;
use Spiral\RoadRunner\Http\PSR7Worker;
use Omega\Application\Application;
use Omega\Http\Http;
use Omega\Http\RequestFactory;

require __DIR__ . '/../vendor/autoload.php';

$app = new Application(__DIR__ . '/..');
$app->setBaseBinding();
$http  = $app->make(Http::class);
$rf    = $app->make(RequestFactory::class);

$worker = $app->make(Worker::class);          // from RR relay
$psr7   = new PSR7Worker($worker);

while ($serverRequest = $psr7->waitRequest()) {   // persistent loop
    try {
        $omegaRequest = $rf->fromPsr7ServerRequest($serverRequest);
        $response     = $http->handle($omegaRequest);   // Omega Response
        $psr7->respond(Psr7Response::fromOmega($response));
    } catch (Throwable $e) {
        $psr7->respond(new Response(500, [], $http->report($e))); // or getStream
    } finally {
        $http->terminate($omegaRequest, $response); // fires terminate + resetForRequest
    }
}
```

`Http::terminate()` already (a) runs middleware terminate, (b) `app->terminate()`, (c) `resetForRequest()`. With §4.1 applied, `resetForRequest()` also re-loads routes, so the loop is self-resetting.

### 4.5 (RECOMMENDED) Clear per-request static/macro state at the boundary

In `AbstractApplication::resetForRequest()` (or `Http::resetForRequest()`), clear the per-request statics:

```php
// AbstractApplication.php::resetForRequest()
Router::reset();                       // existing
AbstractFacade::flushInstance();       // existing
DirectiveTemplator::resetDirectives(); // NEW — clear app-registered directives if any are per-request
Request::resetMacro();                 // NEW — only if macros are registered per request
Response::resetMacro();                // NEW
$this->middlewareUsed = [];            // Http::resetForRequest()
```

Add these exactly once at the start of the worker so they never run per-request (they are process-lifetime): `DirectiveTemplator::register('vite', ...)`, `Model::setEventDispatcher(...)`, `AbstractFacade::setFacadeBase(...)` (already in `FacadeBootstrapper`).

> `Vite::$cache` need only be flushed if you redeploy assets without restarting workers; keep `Vite::flush()` callable from a cache-clear command instead of per request.

### 4.6 (RECOMMENDED) Mark request-scoped bindings

In the kernel or providers, mark the per-request bindings so `resetRequestScope()` rebuilds them:

```php
// Http::handle() — after $this->app->set('request', $request);
$this->app->setRequestScoped('request');
$this->app->setRequestScoped(Http\Request::class);

// If repositories/cache-per-request are desired, also:
$this->app->setRequestScoped('view.response');
```

And ensure any service that stored a `Request` reference is rebuilt: resolve it through the container each request rather than capturing it in another singleton's constructor. This is the correct long-term pattern for "current user", "current request", etc.

### 4.7 Alternative design: preload routes once at worker startup

Rather than re-running route registration every request, you can keep routes as process-lifetime static state and simply **not reset them**:

```php
// public/roadrunner-worker.php (startup)
$app->setBaseBinding();
$app->make(\Omega\Http\Http::class)->bootstrap(); // boot providers once, registering routes
// do NOT call Router::reset() per request — remove it from resetForRequest,
// or override resetForRequest in your app's Http subclass to skip it.
```

This is the lowest-overhead approach and is fully valid because the route table is immutable static data. The only cost is changing `Router::reset()` behavior in the reset path (or guarding it). If you take this route, still call `AbstractFacade::flushInstance()`, request-scope reset, and `Templator::clearDependencies()` each request — those do not touch routes.

**Recommendation:** prefer **4.1** (per-request reload) for correctness and testability, since it keeps `Router::reset()` safe and matches the framework's existing reset semantics. Use **4.7** only if profiling shows route re-registration is a measurable hotspot (it generally is not).

### 4.8 (OPTIONAL) Cron + Schedule notes

`RouteServiceProvider::boot()` also does `require_once routes/schedule.php`, which populates the `Schedule` pool **once** (guarded by `$isBooted`). Under a persistent worker the schedule is registered once and executed by a `CronWorkCommand` — this is correct. Do **not** re-require `schedule.php` per request (it would run `Schedule::call()` repeatedly). If you merge route reloading per request (§4.1), keep schedule loading guarded by a `$scheduleLoaded` flag as shown.

---

## 5. Recommended `.rr.yaml` Configuration

```yaml
version: "3"

rpc:
  listen: tcp://127.0.0.1:6001

server:
  command: "php public/roadrunner-worker.php"   # entry point from §4.4
  relay: pipes

http:
  address: 0.0.0.0:8080
  middleware: [static, gzip, headers]
  pool:
    num_workers: 8
    max_jobs: 10000          # recycle workers periodically to bound memory
    allocate_timeout: 20s
    destroy_timeout: 10s
  headers:
    response:
      X-Powered-By: "Omega/RoadRunner"

logs:
  mode: production
  level: error

metrics:
  address: 127.0.0.1:2112

# Optional: reuse the same pools for a `/health` route by binding a small
# PHP worker that answers from the same Application instance.
```

Notes:
- `max_jobs` recycling bounds any residual per-request growth even after remediation — a best practice for long-lived PHP.
- Use `relay: pipes` (goridge) for the worker entry; `PSR7Worker` uses the standard relay.
- The framework's existing `Http::terminate()` + `resetForRequest()` sequence maps naturally onto the `finally` block of the worker loop.

---

## 6. Testing Recommendations

No multi-request test currently exists (`tests/Tests/Http/KernelTerminateTest.php` and `KernelTest.php` each exercise a single lifecycle). Add an integration test that simulates a persistent worker:

```php
// tests/Tests/Http/RoadRunnerMultiRequestTest.php
public function testRoutesSurviveAcrossRequests(): void
{
    $app = $this->setFixtureBasePath(...);   // fixture with routes/web.php
    $http = $app->make(Http::class);

    // Request 1
    $r1 = RequestFactory::capture();           // or fromPsr7ServerRequest(...)
    $res1 = $http->handle($r1);
    $this->assertSame(200, $res1->getStatusCode());
    $http->terminate($r1, $res1);              // triggers resetForRequest

    // Request 2 — the regression this audit fixes
    $r2 = RequestFactory::capture();
    $res2 = $http->handle($r2);
    $this->assertSame(200, $res2->getStatusCode(), // would be 404 w/o the fix
        'Routes must be re-registered on the 2nd request in a persistent worker.');

    $http->terminate($r2, $res2);
    $app->flush();
}
```

Extend the existing `MemoryLeakTest` pattern (`tests/Tests/Container/MemoryLeakTest.php`) to loop `handle()+terminate()` 1,000 × and assert that `Router::getRoutes()` count, container `instances`/`bindings`, and `Http::$middlewareUsed` do **not** grow.

Also add:
- A PSR-7 → `Request` conversion test (query, body, headers, cookies, files round-trip).
- A test asserting `DirectiveTemplator::$directive` and `MacroableTrait::$macros` are stable across reset (or explicitly reset).
- A DB test verifying `resetConnectionsForRequest()` rolls back an uncommitted transaction between two simulated requests.

---

## 7. Remediation Checklist

| # | Item | Severity | Status |
|---|---|---|---|
| 4.1 | Re-register routes per request (separate from `$isBooted`) | **Critical** | ❌ Required |
| 4.2 | PSR-7 → `Request` adapter; stop reading `$_SERVER` in kernel/router | **Critical** | ❌ Required |
| 4.3 | Cache `ConfigRepository` once per worker | Medium | ⬜ Recommended |
| 4.4 | `public/roadrunner-worker.php` + worker loop | **Required for deploy** | ⬜ Recommended |
| 4.5 | Clear `Http::$middlewareUsed`, per-request macros/directives | Medium | ⬜ Recommended |
| 4.6 | Mark request-scoped bindings (`request`, `view.response`, …) | Medium | ⬜ Recommended |
| 4.7 | (Alt) preload routes once, don't reset router | Low | ⬜ Optional |
| 4.8 | Guard `schedule.php` with `$scheduleLoaded` | Low | ⬜ Optional |
| 5 | `.rr.yaml` with `max_jobs` recycling | Deploy | ⬜ Recommended |
| 6 | Multi-request + memory-leak integration tests | **Quality gate** | ⬜ Recommended |

---

## 8. Bottom Line

The Omega framework was **architected with persistent workers in mind**: the container's request-scope model, facade flushing, persistent-PDO with loss detection, database transaction rollback at the boundary, exception-handler static guard, templator dependency clearing, and bounded memory caches are all the *right* primitives. **Two mandatory changes** stand between it and a working RoadRunner deployment:

1. **Re-register the route table per request** (decouple route loading from `$isBooted` and from `Router::reset()`), and
2. **Bootstrap from the PSR-7 `ServerRequestInterface`** instead of PHP superglobals.

Apply §4.1 and §4.2, then the recommended hardening in §4.3–§4.8 and the `.rr.yaml` in §5, and the framework runs correctly, safely, and efficiently inside a long-lived RoadRunner worker.
