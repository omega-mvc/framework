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

/** @noinspection PhpUnnecessaryCurlyVarSyntaxInspection */

declare(strict_types=1);

namespace Omega\Router;

use Omega\Http\Request;

use function array_filter;
use function array_shift;
use function is_numeric;
use function parse_url;
use function preg_match;
use function rtrim;
use function strtolower;

use const ARRAY_FILTER_USE_KEY;

/**
 * RouteDispatcher is responsible for handling the matching of incoming HTTP requests
 * against a set of defined routes. It evaluates the request URI and HTTP method,
 * applies base path, case sensitivity, and trailing slash rules, and triggers the
 * appropriate callbacks or middleware.
 *
 * This class supports:
 * - Dispatching requests based on method and path.
 * - Route parameter extraction (including named parameters).
 * - Case-sensitive and trailing slash-aware matching.
 * - Multi-route matching.
 * - Middleware execution before the final callback.
 * - Handling of unmatched routes or disallowed HTTP methods.
 *
 * Example usage:
 *
 * ```php
 * $dispatcher = RouteDispatcher::dispatchFrom('/users/123', 'GET', $routes);
 * $dispatcher
 *     ->basePath('/api')
 *     ->caseMatters(true)
 *     ->trailingSlashMatters(false)
 *     ->multiMatch(false)
 *     ->run(
 *         fn($routeCallable, $params) => call_user_func_array($routeCallable, $params),
 *         fn($path) => echo "No route found for $path",
 *         fn($path, $method) => echo "Method $method not allowed for $path"
 *     );
 * ```
 *
 * @category  Omega
 * @package   Router
 * @link      https://omega-mvc.github.io
 * @author    Adriano Giovannini <agisoftt@gmail.com>
 * @copyright Copyright (c) 2025 - 2026 Adriano Giovannini (https://omega-mvc.github.io)
 * @license   https://www.gnu.org/licenses/gpl-3.0-standalone.html     GPL V3.0+
 * @version   2.0.0
 */
final class RouteDispatcher
{
    /** @var Request Incoming HTTP request. */
    private Request $request;

    /** @var Route[] List of routes to match against. */
    private array $routes;

    /** @var callable Callback executed when a matching route is found. */
    private $found;

    /** @var ?callable(string): mixed Callback executed when no matching route is found. */
    private $notFound;

    /** @var ?callable(string, string): mixed Callback executed when the HTTP method is not allowed. */
    private $methodNotAllowed;

    /** @var string Base path to be prepended to route matching. */
    private string $basepath = '';

    /** @var bool Whether the route matching is case-sensitive. */
    private bool $caseMatters = false;

    /** @var bool Whether trailing slash is significant. */
    private bool $trailingSlashMatters = false;

    /** @var bool Whether to return multiple matches or only the first. */
    private bool $multiMatch = false;

    /** @var array<string, mixed> Triggered action with callable and parameters after dispatch. */
    private array $trigger;

    /** @var Route|null The current matched route. */
    private ?Route $current = null;

    /**
     * RouteDispatcher constructor.
     *
     * @param Request $request Incoming request.
     * @param Route[] $routes  Array of Route objects.
     * @return void
     */
    public function __construct(Request $request, array $routes)
    {
        $this->request = $request;
        $this->routes  = $routes;
    }

    /**
     * Create a new dispatcher instance from a URI and HTTP method.
     *
     * @param string  $uri    The request URI.
     * @param string  $method HTTP method.
     * @param Route[] $routes Array of Route objects.
     * @return self
     */
    public static function dispatchFrom(string $uri, string $method, array $routes): self
    {
        $createRequest = new Request($uri, [], [], [], [], [], [], $method);

        return new RouteDispatcher($createRequest, $routes);
    }

    /**
     * Set the base path for route matching.
     *
     * @param string $basePath Base path to prepend to routes.
     * @return self
     */
    public function basePath(string $basePath): self
    {
        $this->basepath = $basePath;

        return $this;
    }

    /**
     * Enable or disable case-sensitive route matching.
     *
     * @param bool $case_matters Whether case matters.
     * @return self
     */
    public function caseMatters(bool $case_matters): self
    {
        $this->caseMatters = $case_matters;

        return $this;
    }

    /**
     * Enable or disable strict trailing slash matching.
     *
     * @param bool $trailingSlashMatters Whether trailing slash matters.
     * @return self
     */
    public function trailingSlashMatters(bool $trailingSlashMatters): self
    {
        $this->trailingSlashMatters = $trailingSlashMatters;

        return $this;
    }

    /**
     * Enable or disable multi-route matching.
     *
     * @param bool $multiMatch Return multiple matches if true.
     * @return self
     */
    public function multiMatch(bool $multiMatch): self
    {
        $this->multiMatch = $multiMatch;

        return $this;
    }

    /**
     * Get the currently matched route after dispatch.
     *
     * @return Route|null Return the current matched route after dispatch.
     */
    public function current(): ?Route
    {
        return $this->current;
    }

    /**
     * Run the dispatcher and trigger the appropriate callbacks.
     *
     * @param callable $found             Callback for a successful route match.
     * @param callable $notFound          Callback if no route matches.
     * @param callable $methodNotAllowed  Callback if the route exists but method is invalid.
     * @return array<string, mixed> Triggered action with 'callable' and 'params'.
     */
    public function run(callable $found, callable $notFound, callable $methodNotAllowed): array
    {
        $this->found            = $found;
        $this->notFound         = $notFound;
        $this->methodNotAllowed = $methodNotAllowed;

        $this->dispatch($this->basepath, $this->caseMatters, $this->trailingSlashMatters, $this->multiMatch);

        return $this->trigger;
    }

    /**
     * Store the triggered callback with its parameters and middleware.
     *
     * @param callable                   $callable   The callback to execute.
     * @param array<int, mixed|string[]> $params     Parameters for the callback.
     * @param class-string[]             $middleware Middleware classes to apply.
     * @return void
     */
    private function trigger(callable $callable, array $params, array $middleware = []): void
    {
        $this->trigger = [
            'callable'      => $callable,
            'params'        => $params,
            'middleware'    => $middleware,
        ];
    }

    /**
     * Dispatch routes and set up the trigger action.
     *
     * @param string $basePath             Base path prefix.
     * @param bool   $caseMatters          Case-sensitive matching.
     * @param bool   $trailingSlashMatters Whether trailing slash is significant.
     * @param bool   $multiMatch           Allow multiple matches.
     * @return void
     */
    private function dispatch(
        string $basePath = '',
        bool $caseMatters = false,
        bool $trailingSlashMatters = false,
        bool $multiMatch = false
    ): void {

        $basePath        = rtrim($basePath, '/');
        $parsedUrl       = parse_url($this->request->getUrl());
        $path            = $this->resolvePath($parsedUrl, $basePath, $trailingSlashMatters);
        $method          = $this->request->getMethod();
        $pathMatchFound  = false;
        $routeMatchFound = false;

        foreach ($this->routes as $route) {
            $expression         = $route['expression'];
            $originalExpression = $expression;
            $expression         = $this->makeRoutePatterns($expression, $route['patterns'] ?? []);

            // Add basepath to matching string
            if ($basePath !== '' && $basePath !== '/') {
                $expression = "({$basePath}){$expression}";
            }

            // Check path match
            if (preg_match("#^{$expression}$#" . ($caseMatters ? '' : 'i') . 'u', $path, $matches)) {
                $pathMatchFound = true;

                // Cast allowed method to array if it's not one already, then run through all methods
                foreach ((array) $route['method'] as $allowedMethod) {
                    // Check method match
                    if (strtolower($method) !== strtolower($allowedMethod)) {
                        continue;
                    }

                    $parameters = $this->resolveNamedParameters($matches);

                    $this->trigger(
                        callable: $this->found,
                        params: [$route['function'], $parameters],
                        middleware: $route['middleware'] ?? []
                    );
                    $this->current               = $route;
                    $this->current['expression'] = "^{$originalExpression}$";
                    $routeMatchFound             = true;
                    break;
                }
            }

            // Break the loop if the first found route is a match
            if ($routeMatchFound && false === $multiMatch) {
                    break;
            }
        }

        // No matching route was found
        if (false === $routeMatchFound) {
            if ($pathMatchFound && $this->methodNotAllowed) {
                $this->trigger($this->methodNotAllowed, [$path, $method]);
            } elseif (false === $pathMatchFound && $this->notFound) {
                $this->trigger($this->notFound, [$path]);
            }
        }
    }

    /**
     * Resolve the request path taking into account base path and trailing slash.
     *
     * @param false|array<string, int|string> $parsedUrl Parsed URL from parse_url().
     * @param string                          $basePath  Base path prefix.
     * @param bool                            $trailingSlashMatters Whether trailing slash matters.
     * @return string Normalized path string.
     */
    private function resolvePath(array|false $parsedUrl, string $basePath, bool $trailingSlashMatters): string
    {
        $parsedPath = $parsedUrl['path'] ?? null;

        /** @noinspection PhpDuplicateMatchArmBodyInspection */
        return match (true) {
            null === $parsedPath           => '/',
            $trailingSlashMatters          => $parsedPath,
            "{$basePath}/" !== $parsedPath => rtrim($parsedPath, '/'),
            default                        => $parsedPath,
        };
    }

    /**
     * Replace placeholders in route expression with actual patterns.
     *
     * @param string                 $expression Route URI expression.
     * @param array<string, string>  $pattern    Pattern replacements.
     * @return string
     */
    private function makeRoutePatterns(string $expression, array $pattern): string
    {
        if ([] === $pattern) {
            return $expression;
        }

        return Router::mapPatterns($expression, $pattern);
    }

    /**
     * Resolve named parameters from preg_match matches.
     *
     * @param string[] $matches Matches from preg_match.
     * @return array<string|int, string> Filtered array of named parameters.
     */
    private function resolveNamedParameters(array $matches): array
    {
        $namedMatches = array_filter($matches, 'is_string', ARRAY_FILTER_USE_KEY);
        if (empty($namedMatches)) {
            array_shift($matches);
            $cleanMatches = $matches;
        } else {
            $cleanMatches = array_filter(
                $namedMatches,
                static fn (string $key): bool => false === is_numeric($key),
                ARRAY_FILTER_USE_KEY
            );
        }

        return $cleanMatches;
    }
}
