<?php

/**
 * Part of Omega - Router Package.
 *
 * @link      https://omega-mvc.github.io
 * @author    Adriano Giovannini <agisoftt@gmail.com>
 * @copyright Copyright (c) 2025 - 2026 Adriano Giovannini (https://omega-mvc.github.io)
 * @license   https://www.gnu.org/licenses/gpl-3.0-standalone.html     GPL V3.0+
 * @version   2.0.0
 */

declare(strict_types=1);

namespace Omega\Router;

use function call_user_func_array;

/**
 * Core routing manager responsible for registering routes, grouping them,
 * applying middleware, resolving attributes, and dispatching incoming HTTP requests.
 *
 * This class maintains a static registry of all defined routes and provides helper
 * methods for route creation (e.g., GET, POST, resource), grouping (prefix, middleware, name),
 * controller-based routing, and attribute-based routing via reflection.
 *
 * Routes are dispatched using a RouteDispatcher and may trigger custom handlers
 * when no matching path or method is found.
 *
 * @category  Omega
 * @package   Router
 * @link      https://omega-mvc.github.io
 * @author    Adriano Giovannini <agisoftt@gmail.com>
 * @copyright Copyright (c) 2025 - 2026 Adriano Giovannini (https://omega-mvc.github.io)
 * @license   https://www.gnu.org/licenses/gpl-3.0-standalone.html     GPL V3.0+
 * @version   2.0.0
 */
class Router extends AbstractRouter
{
    /**
     * Adds a new route to the internal collection if it contains
     * the required fields: expression, function, and method.
     *
     * @param array{
     *     expression:string,
     *     function:callable,
     *     method:string
     * } $route  Route definition.
     * @return void
     */
    public static function addRoutes(array $route): void
    {
        if (
            isset($route['expression'])
            && isset($route['function'])
            && isset($route['method'])
        ) {
            self::$routes[] = new Route($route);
        }
    }

    /**
     * Removes a route from the collection by its name.
     *
     * @param string $routeName  The name of the route to remove.
     * @return void
     */
    public static function removeRoutes(string $routeName): void
    {
        foreach (self::$routes as $name => $route) {
            if ($route['name'] === $routeName) {
                unset(self::$routes[$name]);
            }
        }
    }

    /**
     * Replaces an existing route with a new instance, identified by name.
     *
     * @param string $routeName  The name of the route to replace.
     * @param Route  $newRoute   The new Route instance.
     * @return void
     */
    public static function changeRoutes(string $routeName, Route $newRoute): void
    {
        foreach (self::$routes as $name => $route) {
            if ($route['name'] === $routeName) {
                self::$routes[$name] = $newRoute;
                break;
            }
        }
    }

    /**
     * Merges multiple sets of routes into the current collection.
     *
     * Each element of the array is passed to addRoutes().
     *
     * @param array<int, array> $arrayRoutes  An array of route definitions.
     * @return void
     */
    public static function mergeRoutes(array $arrayRoutes): void
    {
        foreach ($arrayRoutes as $route) {
            self::addRoutes($route);
        }
    }

    /**
     * Sets the callback executed when no matching route is found.
     *
     * @param callable $function Callback to execute.
     * @return void
     */
    public static function pathNotFound(mixed $function): void
    {
        self::$pathNotFound = $function;
    }

    /**
     * Sets the callback executed when a route is found but the HTTP method is not allowed.
     *
     * @param callable $function Callback to execute.
     * @return void
     */
    public static function methodNotAllowed(mixed $function): void
    {
        self::$methodNotAllowed = $function;
    }

    /**
     * Executes the routing process.
     *
     * @param string $basePath             Base path to apply to all routes.
     * @param bool   $caseMatters          Whether matching is case-sensitive.
     * @param bool   $trailingSlashMatters Whether trailing slashes affect matching.
     * @param bool   $multiMatch           Whether multiple routes may be returned.
     * @return mixed                       The result of the matched route callback.
     */
    public static function run(
        string $basePath = '',
        bool $caseMatters = false,
        bool $trailingSlashMatters = false,
        bool $multiMatch = false
    ): mixed {
        $dispatcher = RouteDispatcher::dispatchFrom($_SERVER['REQUEST_URI'], $_SERVER['REQUEST_METHOD'], self::$routes);

        $dispatch = $dispatcher
            ->basePath($basePath)
            ->caseMatters($caseMatters)
            ->trailingSlashMatters($trailingSlashMatters)
            ->multiMatch($multiMatch)
            ->run(
                fn ($current, $params) => call_user_func_array($current, $params),
                fn ($path)             => call_user_func_array(self::$pathNotFound, [$path]),
                fn ($path, $method)    => call_user_func_array(self::$methodNotAllowed, [$path, $method])
            );

        self::$current = $dispatcher->current();

        // Execute middleware
        $middlewareUsed = [];
        foreach ($dispatch['middleware'] as $middleware) {
            if (in_array($middleware, $middlewareUsed)) {
                continue;
            }

            $middlewareUsed[] = $middleware;
            $middlewareClass  = new $middleware();
            $middlewareClass->handle();
        }

        return call_user_func_array($dispatch['callable'], $dispatch['params']);
    }
}
