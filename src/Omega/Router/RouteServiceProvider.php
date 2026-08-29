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
use function is_string;
use function Omega\Application\get_path;
use function str_contains;
use function unserialize;

class RouteServiceProvider extends AbstractServiceProvider
{
    /**
     * @return void
     * @throws BindingResolutionException
     * @throws BindingResolutionException Thrown when resolving a binding fails.
     * @throws CircularAliasException Thrown when alias resolution loops recursively.
     * @throws EntryNotFoundException Thrown when no entry exists for the identifier.
     * @throws ReflectionException Thrown when the requested class or interface cannot be reflected.
     */
    public function boot(): void
    {
        if (file_exists($cache = $this->app->getApplicationCachePath() . 'route.php')) {
            $routes = require $cache;

            foreach (is_array($routes) ? $routes : [] as $route) {
                if (is_array($route)) {
                    $this->registerRoute($route);
                }
            }        } else {
            Router::middleware([
                MaintenanceMiddleware::class,
            ])->group(
                fn () => [
                    require_once get_path('path.base', 'routes/web.php'),
                ]
            );
        }

        require_once get_path('path.base', 'routes/schedule.php');
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

        if (!is_callable($callable) || !is_string($expression) || !is_string($method)) {
            return;
        }

        Router::addRoutes([
            'expression' => $expression,
            'function'   => $callable,
            'method'     => $method,
        ]);
    }
}
