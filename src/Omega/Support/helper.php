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

use Omega\Application\Application;
use Omega\Collection\CollectionImmutable;
use Omega\Container\Exceptions\BindingResolutionException;
use Omega\Container\Exceptions\CircularAliasException;
use Omega\Container\Exceptions\EntryNotFoundException;
use Omega\Exceptions\ApplicationNotAvailableException;
use Omega\Http\RedirectResponse;
use Omega\Http\Response;
use Omega\Router\Router;
use Omega\Router\RouteUrlBuilder;
use Omega\Support\Env;
use Omega\Support\Vite;
use Psr\Container\ContainerExceptionInterface;

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

if (!function_exists('app_env')) {
    /**
     * Check application environment mode.
     *
     * @return string Returns the current environment mode of the application, such as 'dev', 'prod', or 'testing'.
     * @throws BindingResolutionException Thrown when resolving a binding fails.
     * @throws CircularAliasException Thrown when alias resolution loops recursively.
     * @throws ContainerExceptionInterface Thrown on general container errors, e.g., service not retrievable.
     * @throws EntryNotFoundException Thrown when no entry exists for the identifier.
     * @throws ReflectionException Thrown when the requested class or interface cannot be reflected.
     */
    function app_env(): string
    {
        return app()->getEnvironment();
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

if (!function_exists('config')) {
    /**
     * Get Application Configuration.
     *
     * @return CollectionImmutable<string, mixed> Returns an immutable collection
     *  containing all application configuration values, indexed by string keys.
     * @throws BindingResolutionException Thrown when resolving a binding fails.
     * @throws CircularAliasException Thrown when alias resolution loops recursively.
     * @throws ContainerExceptionInterface Thrown on general container errors, e.g., service not retrievable.
     * @throws EntryNotFoundException Thrown when no entry exists for the identifier.
     * @throws ReflectionException Thrown when the requested class or interface cannot be reflected.
     */
    function config(): CollectionImmutable
    {
        return new CollectionImmutable(app()->get('config'));
    }
}

if (!function_exists('view')) {
    /**
     * Render with custom template engine, wrap in `Route\Controller`.
     *
     * @param string               $view_path Path to the template file to render.
     * @param array<string, mixed> $data      Associative array of data to pass to the template.
     * @param array<string, mixed> $option    Optional settings such as 'status' (HTTP status code) and 'header'
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
        $view        = app()->get('view.response');
        $status_code = $option['status'] ?? 200;
        $headers     = $option['header'] ?? [];

        return $view($view_path, $data)
            ->setResponseCode($status_code)
            ->setHeaders($headers);
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
        /** @var Vite $vite */
        $vite = app()->get('vite.gets');

        $resource = $vite->gets($entry_points);
        $first    = array_key_first($resource);

        return 1 === count($resource) ? $resource[$first] : $resource;
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
        $route   = Router::redirect($route_name);
        $builder = new RouteUrlBuilder(Router::$patterns);

        return new RedirectResponse($builder->buildUrl($route, $parameter));
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

if (!function_exists('abort')) {
    /**
     * Abort application to an HTTP exception.
     *
     * @param int $code The HTTP status code for the abort.
     * @param string $message Optional message describing the reason for the abort.
     * @param array<string, string> $headers Optional HTTP headers to send with the response.
     * @return void
     */
    function abort(int $code, string $message = '', array $headers = []): void
    {
        app()->abort($code, $message, $headers);
    }
}

if (!function_exists('env')) {
    function env(string $key, mixed $default = null): mixed
    {
        return Env::get($key, $default);
    }
}

if (!function_exists('set_path')) {
    /**
     * Convert a dot-notated path key into a directory path.
     *
     * This function replaces dots in the given key with the system's directory separator
     * and ensures the path starts and ends with a directory separator.
     *
     * @param string $key The dot-notated path key (e.g., "app.config").
     * @return string The resulting directory path with separators.
     * @throws InvalidArgumentException Thrown when the provided path key is an empty string.
     */
    function set_path(string $key): string
    {
        if ($key === '') {
            throw new InvalidArgumentException('The path key cannot be an empty string.');
        }

        return DIRECTORY_SEPARATOR . str_replace('.', DIRECTORY_SEPARATOR, $key) . DIRECTORY_SEPARATOR;
    }
}

if (!function_exists('get_path')) {
    /**
     * Get application config path, base on config file.
     *
     * @param string|array $id The configuration key(s) used to retrieve the path(s) from the application container.
     * @param string $suffix_path Add string end of path.
     * @return string|array Config path folder or an array of config path folder.
     * @throws BindingResolutionException Thrown when resolving a binding fails.
     * @throws CircularAliasException Thrown when alias resolution loops recursively.
     * @throws ContainerExceptionInterface Thrown on general container errors, e.g., service not retrievable.
     * @throws EntryNotFoundException Thrown when no entry exists for the identifier.
     * @throws ReflectionException Thrown when the requested class or interface cannot be reflected.
     */
    function get_path(string|array $id, string $suffix_path = ''): string|array
    {
        $value = app()->get($id);

        if (is_array($value)) {
            return array_map(fn ($v) => $v . $suffix_path, $value);
        }

        return $value . slash(path: $suffix_path);
    }
}

if (!function_exists('path')) {
    /**
     * Convert a dot-notated binding into a relative directory path.
     *
     * This function replaces dots in the given binding with the system's directory separator
     * and ensures the path ends with a directory separator.
     *
     * @param string $binding The dot-notated binding (e.g., "app.config").
     * @return string The resulting relative directory path with trailing separator.
     */
    function path(string $binding): string
    {
        $relative_path = str_replace('.', DIRECTORY_SEPARATOR, $binding);

        if (!str_ends_with($relative_path, DIRECTORY_SEPARATOR)) {
            $relative_path .= DIRECTORY_SEPARATOR;
        }

        return $relative_path;
    }
}

if (!function_exists('slash')) {
    /**
     * Normalize a filesystem path by converting forward slashes (`/`)
     * into the platform-specific directory separator.
     *
     * This helper is mainly intended for the testing environment, where
     * paths may be manually constructed using `/`, while the application
     * runtime relies on `DIRECTORY_SEPARATOR` depending on the operating
     * system. The function does not alter the meaning of the path; it
     * simply ensures portability and consistency.
     *
     * @param string $path  The path to normalize.
     * @return string       The normalized path with slashes converted.
     */
    function slash(string $path): string
    {
        return str_replace('/', DIRECTORY_SEPARATOR, $path);
    }
}
