<?php

/**
 * Part of Omega - Facades Package.
 *
 * @link      https://omega-mvc.github.io
 * @author    Adriano Giovannini <agisoftt@gmail.com>
 * @copyright Copyright (c) 2025 - 2026 Adriano Giovannini (https://omega-mvc.github.io)
 * @license   https://www.gnu.org/licenses/gpl-3.0-standalone.html     GPL V3.0+
 * @version   2.0.0
 */

declare(strict_types=1);

namespace Omega\Support;

use Exception;
use InvalidArgumentException;
use Omega\Application\Application;
use Omega\Container\Exceptions\BindingResolutionException;
use Omega\Container\Exceptions\CircularAliasException;
use Omega\Container\Exceptions\EntryNotFoundException;
use Omega\Exceptions\ApplicationNotAvailableException;
use Omega\Http\RedirectResponse;
use Omega\Http\Response;
use Omega\Router\Router;
use Omega\Router\RouteUrlBuilder;
use Omega\Support\Env as GetEnv;
use Omega\Support\Vite as GetVite;
use Psr\Container\ContainerExceptionInterface;
use ReflectionException;

use function array_key_first;
use function array_map;
use function count;
use function is_array;
use function str_ends_with;
use function str_replace;
use function strtolower;

use const DIRECTORY_SEPARATOR;
use const PHP_OS_FAMILY;

/**
 * Omega Helper Functions.
 *
 * This file contains global helper functions for accessing
 * core services of the Omega application, such as configuration,
 * environment settings, and utilities to simplify common tasks.
 *
 * @category  Omega
 * @package   Support
 * @link      https://omega-mvc.github.io
 * @author    Adriano Giovannini <agisoftt@gmail.com>
 * @copyright Copyright (c) 2025 - 2026 Adriano Giovannini (https://omega-mvc.github.io)
 * @license   https://www.gnu.org/licenses/gpl-3.0-standalone.html     GPL V3.0+
 * @version   2.0.0
 */

if (!function_exists('app')) {
    /**
     * Get Application container.
     *
     * @return Application Return the current application instance.
     * @throws ApplicationNotAvailableException if the application is not started.
     */
    function app(): Application
    {
        $app = Application::getInstance();
        if (null === $app) {
            throw new ApplicationNotAvailableException();
        }

        return $app;
    }
}

if (!function_exists('env')) {
    /**
     * Retrieve an environment variable value.
     *
     * This helper provides access to environment configuration values
     * through the internal Env component. If the given key does not exist,
     * the provided default value will be returned instead.
     *
     * Example usage:
     * ```php
     * $appEnv = env('APP_ENV', 'production');
     * ```
     *
     * @param string $key The environment variable key.
     * @param mixed $default Optional default value returned if the key is not found.
     * @return mixed The environment value or the default if not set.
     */
    function env(string $key, mixed $default = null): mixed
    {
        return GetEnv::get($key, $default);
    }
}

if (!function_exists('get_path')) {
    /**
     * Retrieve application path(s) from the container and append a suffix.
     *
     * This helper resolves one or more path identifiers from the application
     * container and optionally appends a normalized suffix to each path.
     *
     * The suffix is normalized using the {@see slash()} helper to ensure
     * correct directory separators across platforms.
     *
     * It supports both string and array inputs:
     * - If a single identifier is provided, a string is returned.
     * - If multiple identifiers are provided (array), an array of paths is returned.
     *
     * @param string|array $id The container binding key(s) used to retrieve the path(s).
     * @param string $suffix_path Optional suffix to append to each resolved path.
     * @return string|array The resolved path(s) with the appended suffix.
     * @throws BindingResolutionException If the container binding cannot be resolved.
     * @throws CircularAliasException If a circular alias is detected.
     * @throws ContainerExceptionInterface For general container errors.
     * @throws EntryNotFoundException If no entry is found for the given identifier.
     * @throws ReflectionException If a reflection error occurs during resolution.
     */
    function get_path(string|array $id, string $suffix_path = ''): string|array
    {
        $value = app()->get($id);

        $normalizedSuffix = slash(path: $suffix_path);

        if (is_array($value)) {
            return array_map(fn ($v) => $v . $normalizedSuffix, $value);
        }

        return $value . $normalizedSuffix;
    }
}

if (!function_exists('is_dev')) {
    /**
     * Check application development mode.
     *
     * @return bool True if in dev mode.
     * @throws BindingResolutionException Thrown when resolving a binding fails.
     * @throws CircularAliasException Thrown when alias resolution loops recursively.
     * @throws ContainerExceptionInterface Thrown on general container errors, e.g., service not retrievable.
     * @throws EntryNotFoundException Thrown when no entry exists for the identifier.
     * @throws ReflectionException Thrown when the requested class or interface cannot be reflected.
     */
    function is_dev(): bool
    {
        return app()->isDev();
    }
}

if (!function_exists('is_production')) {
    /**
     * Check application production mode.
     *
     * @return bool True if in production mode.
     * @throws BindingResolutionException Thrown when resolving a binding fails.
     * @throws CircularAliasException Thrown when alias resolution loops recursively.
     * @throws ContainerExceptionInterface Thrown on general container errors, e.g., service not retrievable.
     * @throws EntryNotFoundException Thrown when no entry exists for the identifier.
     * @throws ReflectionException Thrown when the requested class or interface cannot be reflected.
     */
    function is_production(): bool
    {
        return app()->isProduction();
    }
}

if (!function_exists('os_detect')) {
    /**
     * Detect the current operating system.
     *
     * This helper returns a simplified string identifier for the OS on which
     * the PHP script is running. Useful for conditional logic depending on
     * the operating system.
     *
     * Possible return values:
     * - 'windows'
     * - 'linux'
     * - 'mac'
     * - 'bsd'
     * - 'solaris'
     * - 'unknown'
     *
     * Example usage:
     * ```php
     * if (os_detect() === 'windows') {
     *     // Windows-specific code
     * }
     * ```
     *
     * @return string The operating system identifier.
     */
    function os_detect(?string $osFamily = null): string
    {
        $os = $osFamily ?? PHP_OS_FAMILY;

        return match (strtolower($os)) {
            'windows' => 'windows',
            'linux'   => 'linux',
            'darwin'  => 'mac',
            'bsd'     => 'bsd',
            'solaris' => 'solaris',
            default   => 'unknown',
        };
    }
}

if (!function_exists('path')) {
    /**
     * Convert a dot-notated binding into a relative directory path.
     *
     * This function replaces dots in the given binding with the system's directory separator
     * and ensures the path ends with a directory separator.
     *
     * @param string|array $binding The dot-notated binding (e.g., "app.config").
     * @return string|array The resulting relative directory path with trailing separator.
     */
    function path(string|array $binding): string|array
    {
        if (is_array($binding)) {
            return array_map(fn($b) => path($b), $binding);
        }

        $relative_path = str_replace('.', DIRECTORY_SEPARATOR, $binding);

        if (!str_ends_with($relative_path, DIRECTORY_SEPARATOR)) {
            $relative_path .= DIRECTORY_SEPARATOR;
        }

        return $relative_path;
    }
}

if (!function_exists('redirect')) {
    /**
     * Redirect to a specific URL.
     *
     * @param string $url The destination URL for the redirection.
     * @return RedirectResponse Returns a RedirectResponse object representing the redirection.
     * @throws Exception Thrown if the redirection cannot be created.
     */
    function redirect(string $url): RedirectResponse
    {
        return new RedirectResponse($url);
    }
}

if (!function_exists('redirect_route')) {
    /**
     * Redirect to another route using the route's name and optional parameters.
     *
     * @param string $route_name The name of the route to redirect to.
     * @param array<string|int, string|int|bool> $parameter Optional dynamic parameters to populate the
     *                                                      route's URL pattern.
     * @return RedirectResponse Returns a RedirectResponse object representing the redirection.
     * @throws Exception Thrown if the route cannot be resolved or URL cannot be built.
     */
    function redirect_route(string $route_name, array $parameter = []): RedirectResponse
    {
        $route = Router::redirect($route_name);
        $builder = new RouteUrlBuilder(Router::$patterns);

        return new RedirectResponse($builder->buildUrl($route, $parameter));
    }
}

if (!function_exists('set_path')) {
    /**
     * Convert a dot-notated key into an absolute directory path.
     *
     * This helper transforms a dot-notated string (e.g. "app.config")
     * into a filesystem path using the system's directory separator.
     * The resulting path is guaranteed to:
     *
     * - Start with a directory separator
     * - End with a directory separator
     *
     * It supports both string and array inputs. When an array is provided,
     * the transformation is applied recursively to each element.
     *
     * Example:
     * - "app.config" => "/app/config/"
     *
     * @param string|array $key The dot-notated path key(s).
     * @return string|array The resulting normalized directory path(s).
     *
     * @throws InvalidArgumentException If the given key is empty.
     */
    function set_path(string|array $key): string|array
    {
        if (empty($key)) {
            throw new InvalidArgumentException('The path key cannot be an empty string or an empty array.');
        }

        $ds = DIRECTORY_SEPARATOR;

        if (is_array($key)) {
            return array_map(fn($k) => set_path($k), $key);
        }

        return $ds . str_replace('.', $ds, $key) . $ds;
    }
}

if (!function_exists('slash')) {
    /**
     * Normalize filesystem path separators.
     *
     * This helper converts all forward slashes (`/`) in the given path
     * to the platform-specific directory separator (`DIRECTORY_SEPARATOR`).
     *
     * It supports both string and array inputs. When an array is provided,
     * the normalization is applied recursively to each element.
     *
     * This function does not alter the semantic meaning of the path,
     * but ensures consistency across different operating systems.
     *
     * @param string|array $path The path or list of paths to normalize.
     * @return string|array The normalized path(s) with correct directory separators.
     */
    function slash(string|array $path): string|array
    {
        if (is_array($path)) {
            return array_map(fn($p) => str_replace('/', DIRECTORY_SEPARATOR, $p), $path);
        }

        return str_replace('/', DIRECTORY_SEPARATOR, $path);
    }
}

if (!function_exists('vite')) {
    /**
     * Get resource using entry point(s) from the Vite build system.
     *
     * @param string ...$entry_points One or more entry point names to retrieve resources for.
     * @return array<string, string>|string Returns an associative array of entry point names to resource URLs if
     *                                      multiple are given, or a single resource URL string if only one entry point
     *                                      is provided.
     * @throws BindingResolutionException Thrown when resolving a binding fails.
     * @throws CircularAliasException Thrown when alias resolution loops recursively.
     * @throws ContainerExceptionInterface Thrown on general container errors, e.g., service not retrievable.
     * @throws EntryNotFoundException Thrown when no entry exists for the identifier.
     * @throws Exception Thrown when resource cannot be retrieved.
     * @throws ReflectionException Thrown when the requested class or interface cannot be reflected.
     */
    function vite(string ...$entry_points): array|string
    {
        /** @var GetVite $vite */
        $vite = app()->get('vite.gets');

        $resource = $vite->gets($entry_points);
        $first    = array_key_first($resource);

        return 1 === count($resource) ? $resource[$first] : $resource;
    }
}

if (!function_exists('view')) {
    /**
     * Render with custom template engine, wrap in `Route\Controller`.
     *
     * @param string $view_path Path to the template file to render.
     * @param array<string, mixed> $data Associative array of data to pass to the template.
     * @param array<string, mixed> $option Optional settings such as 'status' (HTTP status code) and 'header'
     *           (HTTP headers).
     * @return Response Returns a Response object containing the rendered template along with the specified
     *           status and headers.
     * @throws BindingResolutionException Thrown when resolving a binding fails.
     * @throws CircularAliasException Thrown when alias resolution loops recursively.
     * @throws ContainerExceptionInterface Thrown on general container errors, e.g., service not retrievable.
     * @throws EntryNotFoundException Thrown when no entry exists for the identifier.
     * @throws ReflectionException Thrown when the requested class or interface cannot be reflected.
     */
    function view(string $view_path, array $data = [], array $option = []): Response
    {
        $view = app()->get('view.response');
        $status_code = $option['status'] ?? 200;
        $headers = $option['header'] ?? [];

        return $view($view_path, $data)
            ->setResponseCode($status_code)
            ->setHeaders($headers);
    }
}
