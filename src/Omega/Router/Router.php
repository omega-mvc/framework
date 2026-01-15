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

use Closure;
use Exception;
use Omega\Router\Attribute\Middleware;
use Omega\Router\Attribute\Name;
use Omega\Router\Attribute\Prefix;
use Omega\Router\Attribute\Where;
use ReflectionAttribute;
use ReflectionClass;
use ReflectionException;
use ReflectionMethod;

use function array_any;
use function array_key_exists;
use function array_keys;
use function array_merge;
use function array_values;
use function call_user_func_array;
use function preg_replace_callback;
use function str_replace;

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
class Router
{
    /**
     * List of all registered routes.
     *
     * @var Route[]
     */
    protected static array $routes = [];

    /**
     * Callback triggered when no route matches the requested path.
     *
     * Signature: function(string $path): mixed
     *
     * @var callable(string): mixed|null
     */
    protected static $pathNotFound;

    /**
     * Callback triggered when a route exists but does not support
     * the requested HTTP method.
     *
     * Signature: function(string $path, string $method): mixed
     *
     * @var callable(string, string): mixed|null
     */
    protected static $methodNotAllowed;

    /**
     * Current route grouping context.
     *
     * Supported keys:
     * - 'prefix'     string
     * - 'middleware' string[]
     * - 'as'         string (optional)
     * - 'controller' string (optional)
     *
     * @var array<string, string|string[]>
     */
    public static array $group = [
        'prefix'     => '',
        'middleware' => [],
    ];

    /**
     * The route that matched the current request, if any.
     *
     * @var Route|null
     */
    private static ?Route $current = null;

    /**
     * Alias patterns mapped to their respective regex expressions.
     * Used to convert user-friendly placeholders such as (:id) or (:slug)
     * into valid regex patterns for route matching.
     *
     * @var array<string, string>
     */
    public static array $patterns = [
        '(:id)'   => '(\d+)',
        '(:num)'  => '([0-9]*)',
        '(:text)' => '([a-zA-Z]*)',
        '(:any)'  => '([0-9a-zA-Z_+-]*)',
        '(:slug)' => '([0-9a-zA-Z_-]*)',
        '(:all)'  => '(.*)',
    ];

    /**
     * Converts URL aliases into a full regular expression pattern.
     *
     * Replaces user-defined aliases and expands segments of the form
     * `(name:alias)` into named regex groups.
     *
     * @param string                $url       The URL pattern containing aliases.
     * @param array<string, string> $patterns  Mapping of alias → regex.
     * @return string                          The resulting regular expression.
     */
    public static function mapPatterns(string $url, array $patterns): string
    {
        $userPattern  = array_keys($patterns);
        $allowPattern = array_values($patterns);

        $expression = str_replace($userPattern, $allowPattern, $url);

        return preg_replace_callback(
            '/\((\w+):(\w+)\)/',
            static function (array $matches) use ($patterns): string {
                $pattern = $patterns["(:" . $matches[2] . ")"] ?? '[^/]+';

                //return "(?P<{" . $matches[1] . ">" . $pattern . ")";
                return "(?P<" . $matches[1] . ">" . $pattern . ")";
            },
            $expression
        );
    }

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
     * Returns the list of registered routes in their normalized array form,
     * as provided by Route::route().
     *
     * @return array<int, array<string, mixed>>  The list of routes.
     */
    public static function getRoutes(): array
    {
        $routes = [];
        foreach (self::$routes as $route) {
            $routes[] = $route->route();
        }

        return $routes;
    }

    /**
     * Returns the internal list of Route objects as stored.
     *
     * @return Route[]  Raw Route instances.
     */
    public static function getRoutesRaw(): array
    {
        return self::$routes;
    }

    /**
     * Returns the currently matched route, if any.
     *
     * @return Route|null  The active route or null if none is set.
     */
    public static function getCurrent(): ?Route
    {
        return self::$current;
    }

    /**
     * Resets all router state, including the route list, fallback handlers,
     * and active grouping configuration.
     *
     * @return void
     */
    public static function reset(): void
    {
        self::$routes           = [];
        self::$pathNotFound     = null;
        self::$methodNotAllowed = null;
        self::$group            = [
            'prefix'  => '',
            'as'      => '',
        ];
    }

    /**
     * Creates a route group that applies a shared URL prefix to all routes
     * defined within its scope.
     *
     * @param string $prefix  The URL prefix to apply.
     * @return RouteGroup     A RouteGroup instance managing setup/cleanup.
     */
    public static function prefix(string $prefix): RouteGroup
    {
        $previousPrefix = self::$group['prefix'];

        return new RouteGroup(
            // set up
            function () use ($prefix, $previousPrefix) {
                Router::$group['prefix'] = $previousPrefix . $prefix;
            },
            // reset
            function () use ($previousPrefix) {
                Router::$group['prefix'] = $previousPrefix;
            }
        );
    }

    /**
     * Defines a middleware group that will be applied to all routes created
     * within the returned RouteGroup scope.
     *
     * @param array<int, class-string> $middlewares  List of middleware class names.
     * @return RouteGroup                               A RouteGroup handling setup and reset behavior.
     */
    public static function middleware(array $middlewares): RouteGroup
    {
        $resetGroup = self::$group;

        return new RouteGroup(
            // load middleware
            function () use ($middlewares) {
                self::$group['middleware'] = $middlewares;
            },
            // close middleware
            function () use ($resetGroup) {
                self::$group = $resetGroup;
            }
        );
    }

    /**
     * Defines a name prefix to apply to all routes created inside the returned
     * RouteGroup scope.
     *
     * @param string $name  The name prefix to apply.
     * @return RouteGroup   A RouteGroup handling setup and reset behavior.
     */
    public static function name(string $name): RouteGroup
    {
        return new RouteGroup(
            // setup
            function () use ($name) {
                Router::$group['as'] = $name;
            },
            // reset
            function () {
                Router::$group['as'] = '';
            }
        );
    }

    /**
     * Defines a default controller class for routes created inside the returned
     * RouteGroup scope. Route callbacks can be specified as method names.
     *
     * @param class-string $className  The controller class name.
     * @return RouteGroup              A RouteGroup handling setup and reset behavior.
     */
    public static function controller(string $className): RouteGroup
    {
        // backup current route
        $resetGroup = self::$group;

        return new RouteGroup(
            // setup
            function () use ($className) {
                self::$group['controller'] = $className;
            },
            // reset
            function () use ($resetGroup) {
                self::$group = $resetGroup;
            }
        );
    }

    /**
     * Creates a grouped routing context with custom configuration such as
     * middleware, prefixes, names, or controllers.
     *
     * The provided Closure is executed inside this temporary context and the
     * previous configuration is restored afterward.
     *
     * @param array<string, string|string> $setupGroup  Group configuration options.
     * @param Closure                      $group       The callback defining grouped routes.
     * @return void
     */
    public static function group(array $setupGroup, Closure $group): void
    {
        self::$group['middleware'] ??= [];

        // backup current
        $resetGroup = self::$group;

        $routeGroup = new RouteGroup(
            // setup
            function () use ($setupGroup) {
                foreach ((array) self::$group['middleware'] as $middleware) {
                    $setupGroup['middleware'][] = $middleware;
                }
                self::$group = $setupGroup;
            },
            // reset
            function () use ($resetGroup) {
                self::$group = $resetGroup;
            }
        );

        $routeGroup->group($group);
    }

    /**
     * Checks whether a route with the given name exists.
     *
     * @param string $routeName  The name of the route to check.
     * @return bool              True if a route with that name exists, false otherwise.
     */
    public static function has(string $routeName): bool
    {
        return array_any(self::$routes, fn($route) => $routeName === $route['name']);
    }

    /**
     * Returns the route object associated with a given name.
     * Useful for redirecting to named routes.
     *
     * @param string $to  The name of the route to redirect to.
     * @return Route      The matched Route instance.
     * @throws Exception  If the route name does not exist.
     */
    public static function redirect(string $to): Route
    {
        foreach (self::$routes as $name => $route) {
            if ($route['name'] === $to) {
                return self::$routes[$name];
            }
        }

        throw new Exception('Route name doest exist.');
    }

    /**
     * Registers a REST-style resource controller.
     *
     * Automatically maps CRUD endpoints based on the given URI and controller class.
     * Allows additional configuration via the $setup array (e.g., only, except, missing hooks).
     *
     * @param string   $uri       Base URI for the resource.
     * @param class-string $className Controller class handling the resource.
     * @param array{
     *     map?: array<string, string>,
     *     only?: string[],
     *     except?: string[],
     *     missing?: Closure
     * } $setup  Optional configuration:
     *           - map: custom method → action map
     *           - only: restrict to selected actions
     *           - except: exclude selected actions
     *           - missing: callback for missing resources
     *
     * @return ResourceCollection A collection wrapper for further configuration.
     */
    public static function resource(string $uri, string $className, array $setup = []): ResourceCollection
    {
        $setup['map'] ??= Resource::method();

        $resource = new Resource($uri, $className, $setup['map']);

        if (isset($setup['only'])) {
            $resource->only($setup['only']);
        }
        if (isset($setup['except'])) {
            $resource->except($setup['except']);
        }

        $resource->get()->each(function ($route) {
            self::$routes[] = $route;

            return true;
        });

        $router = new ResourceCollection($className);

        if (array_key_exists('missing', $setup)) {
            $router->missing($setup['missing']);
        }

        return $router;
    }

    /**
     * Registers routes defined using PHP 8 attributes in a class or a set of classes.
     *
     * @param class-string|class-string[] $className  A class name or an array of class names to scan.
     * @return void
     * @throws ReflectionException Thrown when the requested class or interface cannot be reflected.
     */
    public static function register(string|array $className): void
    {
        $classNames = is_string($className) ? [$className] : $className;
        foreach ($classNames as $class) {
            $reflection     = new ReflectionClass($class);
            $routes         = self::resolveRouteAttribute(
                $class,
                $reflection->getAttributes(),
                $reflection->getMethods()
            );
            foreach ($routes as $route) {
                self::$routes[] = new Route($route)->name($route['name'] ?? '');
            }
        }
    }

    /**
     * Resolves routing attributes on a class and its methods, generating route
     * definitions based on annotations such as Prefix, Name, Middleware, and Route.
     *
     * @param string                        $className         The class being processed.
     * @param ReflectionAttribute<object>[] $attributes        Class-level attributes.
     * @param ReflectionMethod[]            $attributesMethods Method-level attributes.
     * @return array<int, array<string, string|array<string, string>>>  Parsed route definitions.
     */
    private static function resolveRouteAttribute(
        string $className,
        array $attributes = [],
        array $attributesMethods = []
    ): array {
        $prefixUri       = '';
        $prefixName      = '';
        $rootMiddlewares = [];
        $classes         = [];

        foreach ($attributes as $classAttribute) {
            $instance = $classAttribute->newInstance();

            if ($instance instanceof Middleware) {
                $rootMiddlewares = $instance->middleware;
            }

            if ($instance instanceof Name) {
                $prefixName = $instance->name;
            }

            if ($instance instanceof Prefix) {
                $prefixUri = $instance->prefix;
            }
        }

        foreach ($attributesMethods as $method) {
            $middlewares = $rootMiddlewares;
            $name        = '';
            $pattern     = [];
            $uri         = '';
            $httpMethod  = '';
            $found       = false;

            foreach ($method->getAttributes() as $attribute) {
                $instance = $attribute->newInstance();

                if ($instance instanceof Middleware) {
                    $middlewares = array_merge($middlewares, $instance->middleware);
                    continue;
                }

                if ($instance instanceof Name) {
                    $name = $instance->name;
                    continue;
                }

                if ($instance instanceof Where) {
                    $pattern = $instance->pattern;
                    continue;
                }

                if ($instance instanceof Attribute\Route\Route) {
                    [
                        'method'     => $httpMethod,
                        'expression' => $uri,
                    ]      = $instance->route;
                    $found = true;
                }
            }

            if (true === $found) {
                $classes[] = [
                    'method'     => $httpMethod,
                    'patterns'   => $pattern,
                    'uri'        => $prefixUri . $uri,
                    'expression' => self::mapPatterns($prefixUri . $uri, self::$patterns),
                    'function'   => [$className, $method->getName()],
                    'middleware' => $middlewares,
                    'name'       => $prefixName . $name,
                ];
            }
        }

        return $classes;
    }

    /**
     * Registers a new route using the given HTTP method(s), URI, and callback.
     *
     * Supports grouped context (prefix, middleware, controller).
     * Pattern expressions are automatically expanded.
     *
     * @param string|string[]                 $method    Allowed HTTP method(s).
     * @param string                          $uri       Route URI or expression.
     * @param callable|string|string[]|array  $callback  A callable, controller method, or handler definition.
     * @return Route                                      The created Route instance.
     */
    public static function match(array|string $method, string $uri, array|callable|string $callback): Route
    {
        $uri = self::$group['prefix'] . $uri;
        if (isset(self::$group['controller']) && is_string($callback)) {
            $callback = [self::$group['controller'], $callback];
        }
        $middleware = self::$group['middleware'] ?? [];

        return self::$routes[] = new Route([
            'method'      => $method,
            'uri'         => $uri,
            'expression'  => self::mapPatterns($uri, self::$patterns),
            'function'    => $callback,
            'middleware'  => $middleware,
        ]);
    }

    /**
     * Registers a new route that matches any HTTP method.
     *
     * @param string   $expression Route pattern or expression.
     * @param callable $function   Callback executed when the route is matched.
     * @return Route
     */
    public static function any(string $expression, mixed $function): Route
    {
        return self::match(['get', 'head', 'post', 'put', 'patch', 'delete', 'options'], $expression, $function);
    }

    /**
     * Registers a new route for the GET method.
     *
     * @param string   $expression Route pattern or expression.
     * @param callable $function   Callback executed when the route is matched.
     * @return Route
     */
    public static function get(string $expression, mixed $function): Route
    {
        return self::match(['get', 'head'], $expression, $function);
    }

    /**
     * Registers a new route for the POST method.
     *
     * @param string   $expression Route pattern or expression.
     * @param callable $function   Callback executed when the route is matched.
     * @return Route
     */
    public static function post(string $expression, mixed $function): Route
    {
        return self::match('post', $expression, $function);
    }

    /**
     * Registers a new route for the PUT method.
     *
     * @param string   $expression Route pattern or expression.
     * @param callable $function   Callback executed when the route is matched.
     * @return Route
     */
    public static function put(string $expression, mixed $function): Route
    {
        return self::match('put', $expression, $function);
    }

    /**
     * Registers a new route for the PATCH method.
     *
     * @param string   $expression Route pattern or expression.
     * @param callable $function   Callback executed when the route is matched.
     * @return Route
     */
    public static function patch(string $expression, mixed $function): Route
    {
        return self::match('patch', $expression, $function);
    }

    /**
     * Registers a new route for the DELETE method.
     *
     * @param string   $expression Route pattern or expression.
     * @param callable $function   Callback executed when the route is matched.
     * @return Route
     */
    public static function delete(string $expression, mixed $function): Route
    {
        return self::match('delete', $expression, $function);
    }

    /**
     * Registers a new route for the OPTIONS method.
     *
     * @param string   $expression Route pattern or expression.
     * @param callable $function   Callback executed when the route is matched.
     * @return Route
     */
    public static function options(string $expression, mixed $function): Route
    {
        return self::match('options', $expression, $function);
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
