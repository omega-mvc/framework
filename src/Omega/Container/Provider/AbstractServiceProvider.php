<?php

/**
 * Part of Omega - Container Package.
 *
 * @link      https://omega-mvc.github.io
 * @author    Adriano Giovannini <agisoftt@gmail.com>
 * @copyright Copyright (c) 2025 - 2026 Adriano Giovannini (https://omega-mvc.github.io)
 * @license   https://www.gnu.org/licenses/gpl-3.0-standalone.html     GPL V3.0+
 * @version   2.0.0
 */

declare(strict_types=1);

namespace Omega\Container\Provider;

use Omega\Application\Application;

use function array_key_exists;
use function array_merge;
use function closedir;
use function copy;
use function file_exists;
use function is_dir;
use function mkdir;
use function opendir;
use function pathinfo;
use function readdir;

use const PATHINFO_DIRNAME;

/**
 * Abstract base class for service providers.
 *
 * Service providers are responsible for registering services and booting logic
 * into the application container.
 *
 * @category   Omega
 * @package    Container
 * @subpackage Provider
 * @link       https://omega-mvc.github.io
 * @author     Adriano Giovannini <agisoftt@gmail.com>
 * @copyright  Copyright (c) 2025 - 2026 Adriano Giovannini (https://omega-mvc.github.io)
 * @license    https://www.gnu.org/licenses/gpl-3.0-standalone.html     GPL V3.0+
 * @version    2.0.0
 */
abstract class AbstractServiceProvider
{
    /** @var array<int|string, class-string> Classes to register in the container */
    protected array $register = [];

    /** @var array<string, array<string, string>> Shared modules available for import from vendor packages */
    protected static array $modules = [];

    /**
     * Create a new service provider instance.
     *
     * @param Application $app The application instance
     */
    public function __construct(protected Application $app)
    {
    }

    /**
     * Boot the service provider.
     *
     * This method is called after all providers are registered.
     */
    public function boot(): void
    {
    }

    /**
     * Register services into the application container.
     *
     * This method should be called before boot.
     */
    public function register(): void
    {
    }

    /**
     * Import a specific file into the application.
     *
     * @param string $from      Source file path
     * @param string $to        Destination file path
     * @param bool   $overwrite Whether to overwrite the destination if it exists
     * @return bool Returns true if the file was successfully imported, false otherwise
     */
    public static function importFile(string $from, string $to, bool $overwrite = false): bool
    {
        $exists = file_exists($to);

        if (($exists && $overwrite) || false === $exists) {
            $path = pathinfo($to, PATHINFO_DIRNAME);
            if (false === file_exists($path)) {
                mkdir($path, 0755, true);
            }

            return copy($from, $to);
        }

        return false;
    }

    /**
     * Import a directory and its contents into the application.
     *
     * @param string $from      Source directory path
     * @param string $to        Destination directory path
     * @param bool   $overwrite Whether to overwrite existing files
     * @return bool Returns true if all files and directories were successfully imported
     */
    public static function importDir(string $from, string $to, bool $overwrite = false): bool
    {
        $dir = opendir($from);

        if (false === $dir) {
            return false;
        }

        if (false === file_exists($to)) {
            mkdir($to, 0755, true);
        }

        while (($file = readdir($dir)) !== false) {
            if ($file === '.' || $file === '..') {
                continue;
            }

            $src = $from . '/' . $file;
            $dst = $to . '/' . $file;

            if (is_dir($src)) {
                if (false === static::importDir($src, $dst, $overwrite)) {
                    closedir($dir);
                    return false;
                }
            } else {
                if (false === static::importFile($src, $dst, $overwrite)) {
                    closedir($dir);
                    return false;
                }
            }
        }

        closedir($dir);
        return true;
    }

    /**
     * Register a package path to the module registry.
     *
     * @param array<string, string> $path Mapping of source to destination paths
     * @param string                $tag  Optional tag to group modules
     */
    public static function export(array $path, string $tag = ''): void
    {
        if (false === array_key_exists($tag, static::$modules)) {
            static::$modules[$tag] = [];
        }

        static::$modules[$tag] = array_merge(static::$modules[$tag], $path);
    }

    /**
     * Get all registered shared modules.
     *
     * @return array<string, array<string, string>> All modules grouped by tag
     */
    public static function getModules(): array
    {
        return static::$modules;
    }

    /**
     * Flush all registered shared modules.
     *
     * @return void
     */
    public static function flushModule(): void
    {
        static::$modules = [];
    }
}
