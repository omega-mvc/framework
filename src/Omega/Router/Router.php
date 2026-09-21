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

use Omega\Router\Attribute\Middleware;
use Omega\Router\Attribute\Name;
use Omega\Router\Attribute\Prefix;
use Omega\Router\Attribute\Where;
use ReflectionAttribute;
use ReflectionClass;
use ReflectionException;
use ReflectionMethod;

use function array_filter;
use function array_find_key;
use function array_walk;
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
 *
 * @phpstan-import-type RouteData from Route
 */
class Router extends AbstractRouter
{
    /**
     * Adds a new route to the internal collection if it contains
     * the required fields: expression, function, and method.
     *
     * @param array{expression:string, function:callable, method:string} $route  Route definition.
     * @return void
     */
    public static function addRoutes(array $route): void
    {
        self::$routes[] = new Route($route);
    }

    /**
     * Removes a route from the collection by its name.
     *
     * @param string $routeName  The name of the route to remove.
     * @return void
     */
    public static function removeRoutes(string $routeName): void
    {
        self::$routes = array_filter(
            self::$routes,
            static fn (Route $route): bool => $route['name'] !== $routeName
        );
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
        $key = array_find_key(
            self::$routes,
            static fn (Route $route): bool => $route['name'] === $routeName
        );

        if (null !== $key) {
            self::$routes[$key] = $newRoute;
        }
    }

    /**
     * Merges multiple sets of routes into the current collection.
     *
     * Each element of the array is passed to addRoutes().
     *
     * @param array<int, array{
     *     expression: string,
     *     function: callable,
     *     method: string
     * }> $arrayRoutes  An array of route definitions.
     * @return void
     */
    public static function mergeRoutes(array $arrayRoutes): void
    {
        array_walk($arrayRoutes, static function (array $route): void {
            self::addRoutes($route);
        });
    }

    /**
     * Registers routes defined using PHP 8 attributes in a class or a set of classes.
     *
     * @param class-string|class-string[] $className A class name or an array of class names to scan.
     * @return void
     * @throws ReflectionException Thrown when the requested class or interface cannot be reflected.
     */
    public static function register(string|array $className): void
    {
        $classNames = is_string($className) ? [$className] : $className;

        array_walk($classNames, static function (string $class): void {
            $reflection = new ReflectionClass($class);

            $routes = self::resolveRouteAttribute(
                $class,
                $reflection->getAttributes(),
                $reflection->getMethods()
            );

            array_walk($routes, static function (array $route): void {
                self::$routes[] = new Route($route);
            });
        });
    }

    /**
     * Resolves routing attributes on a class and its methods, generating route
     * definitions based on annotations such as Prefix, Name, Middleware, and Route.
     *
     * @param string                        $className         The class being processed.
     * @param ReflectionAttribute<object>[] $attributes        Class-level attributes.
     * @param ReflectionMethod[]            $attributesMethods Method-level attributes.
     * @return list<RouteData>              Parsed route definitions.
     */
    private static function resolveRouteAttribute(
        string $className,
        array $attributes = [],
        array $attributesMethods = []
    ): array {
        $prefixUri       = '';
        $prefixName      = '';
        $rootMiddlewares = [];

        array_walk($attributes, static function (ReflectionAttribute $classAttribute) use (&$rootMiddlewares, &$prefixName, &$prefixUri): void {
            $instance = $classAttribute->newInstance();

            if ($instance instanceof Middleware) {
                /** @var array<int, class-string> $rootMiddlewares */
                $rootMiddlewares = $instance->middleware;
            } elseif ($instance instanceof Name) {
                $prefixName = $instance->name;
            } elseif ($instance instanceof Prefix) {
                $prefixUri = $instance->prefix;
            }
        });

        /** @var list<RouteData> $classes */
        $classes = array_values(array_filter(array_map(
            function (ReflectionMethod $method) use ($className, $prefixUri, $prefixName, $rootMiddlewares): ?array {
                $middlewares = $rootMiddlewares;
                $name        = '';
                /** @var array<string, string> $pattern */
                $pattern     = [];
                $uri         = '';
                $httpMethod  = '';
                $found       = false;

                $methodAttributes = $method->getAttributes();
                array_walk($methodAttributes, static function (ReflectionAttribute $attribute) use (
                    &$middlewares,
                    &$name,
                    &$pattern,
                    &$uri,
                    &$httpMethod,
                    &$found
                ): void {
                    $instance = $attribute->newInstance();

                    if ($instance instanceof Middleware) {
                        $middlewares = array_merge($middlewares, $instance->middleware);
                        return;
                    }

                    if ($instance instanceof Name) {
                        $name = $instance->name;
                        return;
                    }

                    if ($instance instanceof Where) {
                        $pattern = $instance->pattern;
                        return;
                    }

                    if ($instance instanceof Attribute\Route\Route) {
                        [
                            'method'     => $httpMethod,
                            'expression' => $uri,
                        ] = $instance->route;
                        $found = true;
                    }
                });

                if (true === $found) {
                    $methodValue = is_array($httpMethod) ? array_values($httpMethod) : $httpMethod;

                    return [
                        'method'     => $methodValue,
                        'patterns'   => $pattern,
                        'uri'        => $prefixUri . $uri,
                        'expression' => self::mapPatterns($prefixUri . $uri, self::$patterns),
                        'function'   => [$className, $method->getName()],
                        'middleware' => array_values($middlewares),
                        'name'       => $prefixName . $name,
                    ];
                }

                return null;
            },
            $attributesMethods
        )));

        return $classes;
    }

    /**
     * Sets the callback executed when no matching route is found.
     *
     * @param callable $function Callback to execute.
     * @return void
     */
    public static function pathNotFound(?callable $function): void
    {
        self::$pathNotFound = $function;
    }

    /**
     * Sets the callback executed when a route is found but the HTTP method is not allowed.
     *
     * @param callable $function Callback to execute.
     * @return void
     */
    public static function methodNotAllowed(?callable $function): void
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
        bool $multiMatch = false,
        ?string $uri = null,
        ?string $method = null
    ): mixed {
        $uri    = $uri    ?? (is_string($_SERVER['REQUEST_URI'] ?? null) ? $_SERVER['REQUEST_URI'] : '/');
        $method = $method ?? (is_string($_SERVER['REQUEST_METHOD'] ?? null) ? $_SERVER['REQUEST_METHOD'] : 'GET');

        $dispatcher = RouteDispatcher::dispatchFrom($uri, $method, self::$routes);

        $dispatch = $dispatcher
            ->basePath($basePath)
            ->caseMatters($caseMatters)
            ->trailingSlashMatters($trailingSlashMatters)
            ->multiMatch($multiMatch)
            ->run(
                fn (callable $current, array $params) => call_user_func_array($current, $params),
                fn (string $path)        => self::$pathNotFound
                    ? call_user_func_array(self::$pathNotFound, [$path])
                    : null,
                fn (string $path, string $method) =>
                    self::$methodNotAllowed ? call_user_func_array(self::$methodNotAllowed, [$path, $method]) : null
            );

        self::$current = $dispatcher->current();

        // Execute middleware
        $middlewareUsed = [];
        $middlewares    = (array) $dispatch['middleware'];
        array_walk($middlewares, static function (string $middleware) use (&$middlewareUsed): void {
            if (in_array($middleware, $middlewareUsed)) {
                return;
            }

            $middlewareUsed[] = $middleware;
            $middlewareClass  = new $middleware();

            if (method_exists($middlewareClass, 'handle')) {
                $middlewareClass->handle();
            }
        });

        return call_user_func_array($dispatch['callable'], $dispatch['params']);
    }
}
