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

use function file_exists;
use function is_array;
use function is_callable;
use function is_file;
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

            if (is_file($schedule)) {
                require $schedule;
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

            foreach (is_array($routes) ? $routes : [] as $route) {
                if (is_array($route)) {
                    $this->registerRoute($route);
                }
            }

            return;
        }

        $webRoutes = get_path('path.base', 'routes/web.php');

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
        $callable = $route['function'] ?? null;

        if (is_string($callable) && str_contains($callable, 'SerializableClosure')) {
            $serialized = unserialize($callable);

            if ($serialized instanceof UnsignedSerializableClosure) {
                $callable = $serialized->getClosure();
            }
        }

        $expression = $route['expression'] ?? '';
        $method     = $route['method'] ?? '';

        if (
            !is_callable($callable)
            || !is_string($expression)
            || (is_array($method) ? empty($method) : !is_string($method))
        ) {
            return;
        }

        if (is_array($method)) {
            foreach ($method as $m) {
                Router::addRoutes([
                    'expression' => $expression,
                    'function'   => $callable,
                    'method'     => $m,
                ]);
            }
        } else {
            Router::addRoutes([
                'expression' => $expression,
                'function'   => $callable,
                'method'     => $method,
            ]);
        }
    }
}
