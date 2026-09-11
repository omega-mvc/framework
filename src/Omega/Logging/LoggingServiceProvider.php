<?php

/**
 * Part of Omega - Logging Package.
 *
 * @link      https://omega-mvc.github.io
 * @author    Adriano Giovannini <agisoftt@gmail.com>
 * @copyright Copyright (c) 2025 - 2026 Adriano Giovannini (https://omega-mvc.github.io)
 * @license   https://www.gnu.org/licenses/gpl-3.0-standalone.html     GPL V3.0+
 * @version   2.0.0
 */

declare(strict_types=1);

namespace Omega\Logging;

use ArrayAccess;
use Omega\Container\AbstractServiceProvider;
use Omega\Container\Exceptions\BindingResolutionException;
use Omega\Container\Exceptions\CircularAliasException;
use Omega\Container\Exceptions\EntryNotFoundException;
use Omega\Logging\Exception\LogArgumentException;
use Psr\Container\ContainerExceptionInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;
use ReflectionException;
use RuntimeException;

use function array_filter;
use function array_keys;
use function array_walk;
use function sprintf;

use const ARRAY_FILTER_USE_KEY;

/**
 * Class LoggingServiceProvider.
 *
 * Registers the logging services in the application container. The provider
 * reads the `logging` configuration and registers every log driver under the
 * `logging.<name>` binding, exposing a {@see LoggingManager} as the default
 * `logging` service.
 *
 * @category   Omega
 * @package    Logging
 * @link       https://omega-mvc.github.io
 * @author     Adriano Giovannini <agisoftt@gmail.com>
 * @copyright  Copyright (c) 2025 - 2026 Adriano Giovannini (https://omega-mvc.github.io)
 * @license   https://www.gnu.org/licenses/gpl-3.0-standalone.html     GPL V3.0+
 * @version    2.0.0
 */
class LoggingServiceProvider extends AbstractServiceProvider
{
    /**
     * Register logging services.
     *
     * @return void
     * @throws BindingResolutionException Thrown when resolving a binding fails.
     * @throws CircularAliasException Thrown when alias resolution loops recursively.
     * @throws ContainerExceptionInterface Thrown on general container errors, e.g., service not retrievable.
     * @throws EntryNotFoundException Thrown when no entry exists for the identifier.
     * @throws ReflectionException Thrown when the requested class or interface cannot be reflected.
     */
    public function boot(): void
    {
        $config = $this->app->get('config');

        if (!is_array($config) && !$config instanceof ArrayAccess) {
            throw new RuntimeException('The config service must be an array or implement ArrayAccess.');
        }

        $logging = $this->normalizeConfig($config['logging'] ?? []);
        $default = $logging['default'] ?? null;

        if (!is_string($default)) {
            throw new RuntimeException('The default logging driver must be a string.');
        }

        $drivers = array_filter(
            $logging,
            static fn (string $name): bool => 'default' !== $name,
            ARRAY_FILTER_USE_KEY
        );

        array_walk($drivers, function (mixed $options, string $name): void {
            if (!is_array($options)) {
                return;
            }

            $this->app->set(
                "logging.$name",
                fn (): LoggerInterface => $this->createDriver($this->normalizeConfig($options))
            );
        });

        $this->app->set('logging', function () use ($default, $drivers): LoggingManager {
            $defaultDriver = $this->app->get("logging.$default");

            if (!$defaultDriver instanceof LoggerInterface) {
                throw new RuntimeException('The default logging driver is not a logger.');
            }

            $manager = new LoggingManager($default, $defaultDriver);

            array_walk($drivers, function (mixed $options, string $name) use ($manager, $default): void {
                if ($name !== $default && is_array($options)) {
                    $driver = $this->app->get("logging.$name");

                    if ($driver instanceof LoggerInterface) {
                        $manager->setDriver($name, $driver);
                    }
                }
            });

            return $manager;
        });
    }

    /**
     * Normalize a raw configuration value into a string-keyed array.
     *
     * @param mixed $value The configuration value.
     * @return array<string, mixed> The normalized configuration array.
     */
    private function normalizeConfig(mixed $value): array
    {
        if (!is_array($value)) {
            return [];
        }

        $normalized = [];

        foreach ($value as $key => $item) {
            if (is_string($key)) {
                $normalized[$key] = $item;
            }
        }

        return $normalized;
    }

    /**
     * Create the configured logger driver instance.
     *
     * @param array<string,mixed> $config Driver configuration.
     * @return LoggerInterface
     * @throws LogArgumentException When an unsupported logger type is configured.
     */
    private function createDriver(array $config): LoggerInterface
    {
        $type = $config['type'] ?? '';

        if ($type === 'stream') {
            $path    = $config['path'] ?? '';
            $minimum = $config['minimum'] ?? LogLevel::DEBUG;
            $options = $this->normalizeConfig($config['options'] ?? []);

            if (!is_string($path) || !is_string($minimum)) {
                throw new LogArgumentException('Unable to create the stream logger: invalid configuration.');
            }

            return new Stream($path, $minimum, $options);
        }

        throw new LogArgumentException(
            sprintf('Unsupported logger type [%s].', is_string($type) ? $type : 'unknown')
        );
    }
}
