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

use Omega\Container\AbstractServiceProvider;
use Omega\Container\Exceptions\BindingResolutionException;
use Omega\Container\Exceptions\CircularAliasException;
use Omega\Container\Exceptions\EntryNotFoundException;
use Omega\Logging\Exception\LogArgumentException;
use Psr\Container\ContainerExceptionInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;
use ReflectionException;

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
 * @license    https://www.gnu.org/licenses/gpl-3.0-standalone.html     GPL V3.0+
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
        $config  = $this->app->get('config');
        $logging = $config['logging'];
        $default = $logging['default'];
        $drivers = array_filter(
            $logging,
            static fn (string $name): bool => 'default' !== $name,
            ARRAY_FILTER_USE_KEY
        );

        array_walk($drivers, function (array $options, string $name): void {
            $this->app->set(
                "logging.$name",
                fn () => $this->createDriver($options)
            );
        });

        $this->app->set('logging', function () use ($default, $drivers): LoggingManager {
            $manager = new LoggingManager($default, $this->app["logging.$default"]);

            array_walk($drivers, function (array $options, string $name) use ($manager, $default): void {
                if ($name !== $default) {
                    $manager->setDriver($name, $this->app["logging.$name"]);
                }
            });

            return $manager;
        });
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
        return match ($config['type'] ?? '') {
            'stream' => new Stream(
                $config['path'],
                $config['minimum'] ?? LogLevel::DEBUG,
                $config['options'] ?? []
            ),
            default => throw new LogArgumentException(
                sprintf(
                    'Unsupported logger type [%s].',
                    $config['type'] ?? ''
                )
            ),
        };
    }
}
