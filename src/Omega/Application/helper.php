<?php

/**
 * Part of Omega - Application Package.
 *
 * @link      https://omega-mvc.github.io
 * @author    Adriano Giovannini <agisoftt@gmail.com>
 * @copyright Copyright (c) 2025 - 2026 Adriano Giovannini (https://omega-mvc.github.io)
 * @license   https://www.gnu.org/licenses/gpl-3.0-standalone.html     GPL V3.0+
 * @version   2.0.0
 */

declare(strict_types=1);

/**
 * Application helper functions.
 *
 * Provides globally accessible helper functions for interacting with the
 * application container, resolving application paths, detecting the current
 * environment, and normalizing filesystem paths.
 *
 * These helpers are loaded as part of the Application package and are only
 * declared when they are not already available in the current runtime.
 *
 * @category  Omega
 * @package   Application
 * @link      https://omega-mvc.github.io
 * @author    Adriano Giovannini <agisoftt@gmail.com>
 * @copyright Copyright (c) 2025 - 2026 Adriano Giovannini (https://omega-mvc.github.io)
 * @license   https://www.gnu.org/licenses/gpl-3.0-standalone.html     GPL V3.0+
 * @version   2.0.0
 */

namespace Omega\Application;

use InvalidArgumentException;
use Omega\Container\Exceptions\BindingResolutionException;
use Omega\Container\Exceptions\CircularAliasException;
use Omega\Container\Exceptions\EntryNotFoundException;
use Omega\Exceptions\ApplicationNotAvailableException;
use Psr\Container\ContainerExceptionInterface;
use ReflectionException;

use function array_map;
use function is_array;
use function str_ends_with;
use function str_replace;
use function strtolower;

use const DIRECTORY_SEPARATOR;
use const PHP_OS_FAMILY;

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
     * @param array<int, string>|string $id The container binding key(s) used to retrieve the path(s).
     * @param string $suffix_path Optional suffix to append to each resolved path.
     * @return array<int|string, string>|string The resolved path(s) with the appended suffix. An
     *         associative array is returned when the resolved binding holds a map of paths.
     * @throws BindingResolutionException If the container binding cannot be resolved.
     * @throws CircularAliasException If a circular alias is detected.
     * @throws ContainerExceptionInterface For general container errors.
     * @throws EntryNotFoundException If no entry is found for the given identifier.
     * @throws ReflectionException If a reflection error occurs during resolution.
     */
    function get_path(array|string $id, string $suffix_path = ''): array|string
    {
        if (is_array($id)) {
            $result = [];

            foreach ($id as $single) {
                $path = get_path($single, $suffix_path);

                $result[] = is_string($path) ? $path : '';
            }

            return $result;
        }

        $value = app()->get($id);

        $normalizedSuffix = slash(path: $suffix_path);

        if (is_array($value)) {
            return array_map(
                static fn (mixed $element): string => is_string($element)
                    ? $element . $normalizedSuffix
                    : $normalizedSuffix,
                $value
            );
        }

        return is_string($value) ? $value . $normalizedSuffix : $normalizedSuffix;
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
     * @param array<int, string>|string $binding The dot-notated binding (e.g., "app.config").
     * @return ($binding is array<int, string> ? array<int, string> : string) Directory path with trailing separator.
     */
    function path(array|string $binding): array|string
    {
        if (is_array($binding)) {
            return array_map(static fn (string $part): string => path($part), $binding);
        }

        $relative_path = str_replace('.', DIRECTORY_SEPARATOR, $binding);

        if (!str_ends_with($relative_path, DIRECTORY_SEPARATOR)) {
            $relative_path .= DIRECTORY_SEPARATOR;
        }

        return $relative_path;
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
     * @param array<int, string>|string $key The dot-notated path key(s).
     * @return ($key is array<int, string> ? array<int, string> : string) The resulting normalized directory path(s).
     *
     * @throws InvalidArgumentException If the given key is empty.
     */
    function set_path(array|string $key): array|string
    {
        if (empty($key)) {
            throw new InvalidArgumentException('The path key cannot be an empty string or an empty array.');
        }

        $ds = DIRECTORY_SEPARATOR;

        if (is_array($key)) {
            return array_map(static fn (string $part): string => set_path($part), $key);
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
     * @param array<int, string>|string $path The path or list of paths to normalize.
     * @return ($path is array<int, string> ? array<int, string> : string) Normalized path(s) with correct separators.
     */
    function slash(array|string $path): array|string
    {
        if (is_array($path)) {
            return array_map(
                static fn (string $part): string => str_replace('/', DIRECTORY_SEPARATOR, $part),
                $path
            );
        }

        return str_replace('/', DIRECTORY_SEPARATOR, $path);
    }
}
