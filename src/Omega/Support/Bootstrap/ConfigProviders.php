<?php

/**
 * Part of Omega - Bootstrap Package.
 *
 * @link      https://omega-mvc.github.io
 * @author    Adriano Giovannini <agisoftt@gmail.com>
 * @copyright Copyright (c) 2025 - 2026 Adriano Giovannini (https://omega-mvc.github.io)
 * @license   https://www.gnu.org/licenses/gpl-3.0-standalone.html     GPL V3.0+
 * @version   2.0.0
 */

declare(strict_types=1);

namespace Omega\Support\Bootstrap;

use Omega\Application\Application;
use Omega\Config\ConfigRepository;
use Omega\Container\Exceptions\BindingResolutionException;
use Omega\Container\Exceptions\CircularAliasException;
use Omega\Container\Exceptions\EntryNotFoundException;
use Psr\Container\ContainerExceptionInterface;
use ReflectionException;
use RuntimeException;

use function array_merge;
use function date_default_timezone_set;
use function file_exists;
use function gettype;
use function glob;
use function is_array;

/**
 * ConfigProviders is responsible for loading and bootstrapping the application's configuration.
 *
 * It supports both cached configuration (from `config.php` in the application cache)
 * and dynamic configuration loading from PHP files in the configured config directory.
 * After loading, it initializes the application's configuration repository and sets
 * the default timezone.
 *
 * @category   Omega
 * @package    Support
 * @subpackage Bootstrap
 * @link       https://omega-mvc.github.io
 * @author     Adriano Giovannini <agisoftt@gmail.com>
 * @copyright  Copyright (c) 2025 - 2026 Adriano Giovannini (https://omega-mvc.github.io)
 * @license    https://www.gnu.org/licenses/gpl-3.0-standalone.html     GPL V3.0+
 * @version    2.0.0
 */
class ConfigProviders
{
    /**
     * Bootstrap configuration for the given application instance.
     *
     * This method attempts to load configuration from a cached file if available;
     * otherwise, it loads all PHP config files from the `path.config` directory
     * and merges them into a single configuration array.
     *
     * @param Application $app The application instance to load configuration into
     * @return void
     * @throws BindingResolutionException Thrown when resolving a binding fails.
     * @throws CircularAliasException Thrown when alias resolution loops recursively.
     * @throws ContainerExceptionInterface Thrown on general container errors, e.g., service not retrievable.
     * @throws EntryNotFoundException Thrown when no entry exists for the identifier.
     * @throws ReflectionException Thrown when the requested class or interface cannot be reflected.
     * @throws RuntimeException If a cached config file or a regular config file does not return an array
     */
    public function bootstrap(Application $app): void
    {
        $configPath = get_path('path.config');
        $config     = [];
        $hasCache   = false;

        // Attempt to load cached configuration
        if (file_exists($file = $app->getApplicationCachePath() . 'config.php')) {
            $cachedConfig = require $file;

            if (!is_array($cachedConfig)) {
                throw new RuntimeException(
                    "Invalid config cache file: expected array, got " . gettype($cachedConfig)
                );
            }

            $config   = $cachedConfig;
            $hasCache = true;
        }

        // Load configuration files dynamically if cache is missing
        if (!$hasCache) {
            foreach (glob($configPath . "*.php") as $path) {
                $value = require $path;

                if (!is_array($value)) {
                    throw new RuntimeException(
                        "Invalid config file [$path]: expected array, got " . gettype($value)
                    );
                }

                $config = array_merge($config, $value);
            }
        }

        // Initialize configuration repository in the application
        $app->loadConfig(new ConfigRepository($config));

        // Set default timezone from environment variable or fallback
        date_default_timezone_set(env('APP_TIMEZONE') ?? 'UTC');
    }
}
