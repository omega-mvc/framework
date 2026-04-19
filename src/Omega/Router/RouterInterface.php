<?php

declare(strict_types=1);

namespace Omega\Router;

use Closure;
use Exception;

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
interface RouterInterface
{
    /**
     * Returns the list of registered routes in their normalized array form,
     * as provided by Route::route().
     *
     * @return array<int, array<string, mixed>>  The list of routes.
     */
    public static function getRoutes(): array;

    /**
     * Returns the internal list of Route objects as stored.
     *
     * @return Route[]  Raw Route instances.
     */
    public static function getRoutesRaw(): array;

    /**
     * Returns the currently matched route, if any.
     *
     * @return Route|null  The active route or null if none is set.
     */
    public static function getCurrent(): ?Route;

    /**
     * Resets all router state, including the route list, fallback handlers,
     * and active grouping configuration.
     *
     * @return void
     */
    public static function reset(): void;

    /**
     * Creates a route group that applies a shared URL prefix to all routes
     * defined within its scope.
     *
     * @param string $prefix The URL prefix to apply.
     * @return RouteGroup     A RouteGroup instance managing setup/cleanup.
     */
    public static function prefix(string $prefix): RouteGroup;

    /**
     * Defines a middleware group that will be applied to all routes created
     * within the returned RouteGroup scope.
     *
     * @param array<int, class-string> $middlewares List of middleware class names.
     * @return RouteGroup                               A RouteGroup handling setup and reset behavior.
     */
    public static function middleware(array $middlewares): RouteGroup;

    /**
     * Creates a grouped routing context with custom configuration such as
     * middleware, prefixes, names, or controllers.
     *
     * The provided Closure is executed inside this temporary context and the
     * previous configuration is restored afterward.
     *
     * @param array<string, string|string> $setupGroup Group configuration options.
     * @param Closure $group The callback defining grouped routes.
     * @return void
     */
    public static function group(array $setupGroup, Closure $group): void;

    /**
     * Checks whether a route with the given name exists.
     *
     * @param string $routeName The name of the route to check.
     * @return bool              True if a route with that name exists, false otherwise.
     */
    public static function has(string $routeName): bool;

    /**
     * Returns the route object associated with a given name.
     * Useful for redirecting to named routes.
     *
     * @param string $to The name of the route to redirect to.
     * @return Route      The matched Route instance.
     * @throws Exception  If the route name does not exist.
     */
    public static function redirect(string $to): Route;

    /**
     * Registers a new route using the given HTTP method(s), URI, and callback.
     *
     * Supports grouped context (prefix, middleware, controller).
     * Pattern expressions are automatically expanded.
     *
     * @param string|string[] $method Allowed HTTP method(s).
     * @param string $uri Route URI or expression.
     * @param callable|string|string[]|array $callback A callable, controller method, or handler definition.
     * @return Route                                      The created Route instance.
     */
    public static function match(array|string $method, string $uri, array|callable|string $callback): Route;

    /**
     * Registers a new route that matches any HTTP method.
     *
     * @param string $expression Route pattern or expression.
     * @param callable $function Callback executed when the route is matched.
     * @return Route
     */
    public static function any(string $expression, mixed $function): Route;

    /**
     * Registers a new route for the GET method.
     *
     * @param string $expression Route pattern or expression.
     * @param callable $function Callback executed when the route is matched.
     * @return Route
     */
    public static function get(string $expression, mixed $function): Route;

    /**
     * Registers a new route for the POST method.
     *
     * @param string $expression Route pattern or expression.
     * @param callable $function Callback executed when the route is matched.
     * @return Route
     */
    public static function post(string $expression, mixed $function): Route;

    /**
     * Registers a new route for the PUT method.
     *
     * @param string $expression Route pattern or expression.
     * @param callable $function Callback executed when the route is matched.
     * @return Route
     */
    public static function put(string $expression, mixed $function): Route;

    /**
     * Registers a new route for the PATCH method.
     *
     * @param string $expression Route pattern or expression.
     * @param callable $function Callback executed when the route is matched.
     * @return Route
     */
    public static function patch(string $expression, mixed $function): Route;

    /**
     * Registers a new route for the DELETE method.
     *
     * @param string $expression Route pattern or expression.
     * @param callable $function Callback executed when the route is matched.
     * @return Route
     */
    public static function delete(string $expression, mixed $function): Route;

    /**
     * Registers a new route for the OPTIONS method.
     *
     * @param string $expression Route pattern or expression.
     * @param callable $function Callback executed when the route is matched.
     * @return Route
     */
    public static function options(string $expression, mixed $function): Route;

    /**
     * Sets the callback executed when no matching route is found.
     *
     * @param callable $function Callback to execute.
     * @return void
     */
    public static function pathNotFound(mixed $function): void;

    /**
     * Sets the callback executed when a route is found but the HTTP method is not allowed.
     *
     * @param callable $function Callback to execute.
     * @return void
     */
    public static function methodNotAllowed(mixed $function): void;

    /**
     * Executes the routing process.
     *
     * @param string $basePath Base path to apply to all routes.
     * @param bool $caseMatters Whether matching is case-sensitive.
     * @param bool $trailingSlashMatters Whether trailing slashes affect matching.
     * @param bool $multiMatch Whether multiple routes may be returned.
     * @return mixed                       The result of the matched route callback.
     */
    public static function run(
        string $basePath = '',
        bool $caseMatters = false,
        bool $trailingSlashMatters = false,
        bool $multiMatch = false
    ): mixed;
}
