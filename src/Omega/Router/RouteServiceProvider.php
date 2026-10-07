<?php

declare(strict_types=1);

namespace Omega\Router;

use Omega\Container\Exceptions\BindingResolutionException;
use Omega\Container\Exceptions\CircularAliasException;
use Omega\Container\Exceptions\EntryNotFoundException;
use Omega\Middleware\MaintenanceMiddleware;
use Omega\Router\Router;
use Omega\Container\AbstractServiceProvider;
use ReflectionException;
use Omega\SerializableClosure\UnsignedSerializableClosure;

use function array_filter;
use function array_walk;
use function count;
use function file_exists;
use function is_array;
use function is_callable;
use function is_file;
use function is_object;
use function is_string;
use function Omega\Application\get_path;
use function str_contains;
use function unserialize;

class RouteServiceProvider extends AbstractServiceProvider
{
    /**
     * Indicates whether the cron schedule has already been loaded for this process.
     *
     * The schedule is process-lifetime configuration and must only be
     * registered once per worker, even though the web routes are re-loaded
     * on every request in a persistent worker (e.g. RoadRunner).
     *
     * @var bool
     */
    protected static bool $scheduleLoaded = false;

    /**
     * Boot the route service provider.
     *
     * Called exactly once per process from `bootProvider()`. Registers the
     * web routes (from the route cache or the web routes file) and loads the
     * cron schedule exactly once.
     *
     * @return void
     * @throws BindingResolutionException
     * @throws BindingResolutionException Thrown when resolving a binding fails.
     * @throws CircularAliasException Thrown when alias resolution loops recursively.
     * @throws EntryNotFoundException Thrown when no entry exists for the identifier.
     * @throws ReflectionException Thrown when the requested class or interface cannot be reflected.
     */
    public function boot(): void
    {
        $this->registerWebRoutes();

        if (false === self::$scheduleLoaded) {
            $schedule = get_path('path.base', 'routes/schedule.php');

            if (is_string($schedule)) {
            if (is_file($schedule)) {
                require $schedule;
            }
        }

            self::$scheduleLoaded = true;
        }
    }

    /**
     * (Re)register only the web routes.
     *
     * Called every request in a persistent worker to repopulate the static
     * route table after it has been cleared by `Router::reset()`. The web
     * routes file uses `require` instead of `require_once` so it re-executes
     * on each call. The cron schedule is intentionally NOT loaded here; it is
     * handled once per process by `boot()`.
     *
     * @return void
     * @throws BindingResolutionException Thrown when resolving a binding fails.
     * @throws CircularAliasException Thrown when alias resolution loops recursively.
     * @throws EntryNotFoundException Thrown when no entry exists for the identifier.
     * @throws ReflectionException Thrown when the requested class or interface cannot be reflected.
     */
    public function registerWebRoutes(): void
    {
        if (file_exists($cache = $this->app->getApplicationCachePath() . 'route.php')) {
            $routes = require $cache;

            $routeDefinitions = is_array($routes) ? $routes : [];
            array_walk($routeDefinitions, function (mixed $route): void {
                if (is_array($route)) {
                    $this->registerRoute($route);
                }
            });

            return;
        }

        $webRoutes = get_path('path.base', 'routes/web.php');

        if (!is_string($webRoutes)) {
            return;
        }

        if (!is_file($webRoutes)) {
            return;
        }

        Router::middleware([
            MaintenanceMiddleware::class,
        ])->group(
            fn () => [
                require $webRoutes,
            ]
        );
    }

    /**
     * Register a single cached route definition.
     *
     * @param array<mixed, mixed> $route The cached route definition.
     * @return void
     */
    private function registerRoute(array $route): void
    {
        $callable = $this->resolveRouteCallable($route['function'] ?? null);

        if ($callable === null) {
            return;
        }

        $expression = $route['expression'] ?? '';
        $method     = $route['method'] ?? '';

        if (!is_string($expression)) {
            return;
        }

        if (is_array($method)) {
            if (empty($method)) {
                return;
            }

            $methods = array_filter($method, 'is_string');
        } elseif (!is_string($method)) {
            return;
        } else {
            $methods = [$method];
        }

        array_walk($methods, static function (string $m) use ($expression, $callable): void {
            Router::addRoutes([
                'expression' => $expression,
                'function'   => $callable,
                'method'     => $m,
            ]);
        });
    }

    /**
     * Resolve the route callable from a cached route definition.
     *
     * Serializable-closure strings are unserialized back into their closure
     * when they carry an unsigned signature; plain strings and raw closures
     * must be callable or the route is skipped. Handler arrays shaped as
     * `[object|string, string]` (e.g. `[Controller::class, 'handle']`) are
     * returned as-is even when the method is not static: `is_callable()`
     * rejects a non-static controller method called on the class name, while
     * the dispatcher resolves the pair through the container — exactly what
     * happens for the very same array when it comes from `routes/web.php`
     * instead of the cache. Arrays of any other shape (wrong arity, non-string
     * method, ...) are still skipped.
     *
     * @param mixed $callable The raw callable value from the route cache.
     * @return callable|array{0: object|string, 1: string}|null The resolved
     *                             callable or handler pair, or null when the
     *                             cached value cannot be used as a route
     *                             callable.
     */
    private function resolveRouteCallable(mixed $callable): callable|array|null
    {
        if (is_array($callable)) {
            return $this->isHandlerPair($callable) ? $callable : null;
        }

        if (!is_string($callable)) {
            if (!is_callable($callable)) {
                return null;
            }

            return $callable;
        }

        if (!str_contains($callable, 'SerializableClosure')) {
            if (!is_callable($callable)) {
                return null;
            }

            return $callable;
        }

        $serialized = unserialize($callable);

        if (!$serialized instanceof UnsignedSerializableClosure) {
            return null;
        }

        return $serialized->getClosure();
    }

    /**
     * Determine whether a value is a `[object|string, string]` handler pair.
     *
     * Mirrors the two-element shape PHP uses for array callables without
     * requiring the method to be static, so a cached
     * `[Controller::class, 'handle']` survives the cache read. Pairs with a
     * wrong arity or with non object/string and non-string slots are rejected.
     *
     * @param array<mixed, mixed> $value The array to inspect.
     * @return bool True when the array is exactly a two-element handler pair.
     * @phpstan-assert-if-true array{0: object|string, 1: string} $value
     */
    private function isHandlerPair(array $value): bool
    {
        if (2 !== count($value) || !isset($value[0], $value[1])) {
            return false;
        }

        if (!is_object($value[0]) && !is_string($value[0])) {
            return false;
        }

        return is_string($value[1]);
    }
}
