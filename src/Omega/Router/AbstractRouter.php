<?php

declare(strict_types=1);

namespace Omega\Router;

use Closure;
use Omega\Router\Exception\RouteNotFoundException;

use function array_any;
use function array_keys;
use function array_values;
use function preg_replace_callback;
use function str_replace;

abstract class AbstractRouter implements RouterInterface
{
    /**
     * List of all registered routes.
     *
     * @var Route[]
     */
    protected static array $routes = [];

    /**
     * The route that matched the current request, if any.
     *
     * @var Route|null
     */
    protected static ?Route $current = null;

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
     *
     * @var array<string, string|string[]>
     */
    public static array $group = [
        'prefix'     => '',
        'middleware' => [],
    ];

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
     * {@inheritdoc}
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
     * {@inheritdoc}
     */
    public static function getRoutesRaw(): array
    {
        return self::$routes;
    }

    /**
     * {@inheritdoc}
     */
    public static function getCurrent(): ?Route
    {
        return self::$current;
    }

    /**
     * {@inheritdoc}
     */
    public static function reset(): void
    {
        self::$routes           = [];
        self::$pathNotFound     = null;
        self::$methodNotAllowed = null;
        self::$group            = [
            'prefix'     => '',
            'middleware' => [],
        ];
    }

    /**
     * {@inheritdoc}
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
     * {@inheritdoc}
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
     * {@inheritdoc}
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
     * {@inheritdoc}
     */
    public static function has(string $routeName): bool
    {
        return array_any(self::$routes, fn($route) => $routeName === $route['name']);
    }

    /**
     * {@inheritdoc}
     *
     * @throws RouteNotFoundException  If the route name does not exist.
     */
    public static function redirect(string $to): Route
    {
        foreach (self::$routes as $name => $route) {
            if ($route['name'] === $to) {
                return self::$routes[$name];
            }
        }

        throw new RouteNotFoundException($to);
    }

    /**
     * {@inheritdoc}
     */
    public static function match(array|string $method, string $uri, array|callable|string $callback): Route
    {
        $uri        = self::$group['prefix'] . $uri;
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
     * {@inheritdoc}
     */
    public static function any(string $expression, mixed $function): Route
    {
        return self::match(['get', 'head', 'post', 'put', 'patch', 'delete', 'options'], $expression, $function);
    }

    /**
     * {@inheritdoc}
     */
    public static function get(string $expression, mixed $function): Route
    {
        return self::match(['get', 'head'], $expression, $function);
    }

    /**
     * {@inheritdoc}
     */
    public static function post(string $expression, mixed $function): Route
    {
        return self::match('post', $expression, $function);
    }

    /**
     * {@inheritdoc}
     */
    public static function put(string $expression, mixed $function): Route
    {
        return self::match('put', $expression, $function);
    }

    /**
     * {@inheritdoc}
     */
    public static function patch(string $expression, mixed $function): Route
    {
        return self::match('patch', $expression, $function);
    }

    /**
     * {@inheritdoc}
     */
    public static function delete(string $expression, mixed $function): Route
    {
        return self::match('delete', $expression, $function);
    }

    /**
     * {@inheritdoc}
     */
    public static function options(string $expression, mixed $function): Route
    {
        return self::match('options', $expression, $function);
    }

    /**
     * {@inheritdoc}
     */
    abstract public static function run(
        string $basePath = '',
        bool $caseMatters = false,
        bool $trailingSlashMatters = false,
        bool $multiMatch = false
    ): mixed;

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
}
