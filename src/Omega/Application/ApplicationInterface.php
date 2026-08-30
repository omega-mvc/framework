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

namespace Omega\Application;

use Omega\Config\ConfigRepository;
use Omega\Container\AbstractServiceProvider;
use Omega\Container\ContainerInterface;

/**
 * Defines the contract for an application instance.
 *
 * The ApplicationInterface represents the core entry point of the framework
 * and coordinates configuration loading, environment detection, service
 * provider registration, bootstrapping, and application lifecycle management.
 *
 * This interface extends ContainerInterface, ensuring that any application
 * instance is also a fully-featured dependency injection container.
 *
 * Implementations are expected to manage:
 * - Application configuration and environment
 * - Service provider registration and booting
 * - Application bootstrapping lifecycle
 * - Maintenance and termination handling
 * - Global application state access (singleton-style, if desired)
 *
 * @category  Omega
 * @package   Application
 * @link      https://omega-mvc.github.io
 * @author    Adriano Giovannini <agisoftt@gmail.com>
 * @copyright Copyright (c) 2025 - 2026 Adriano Giovannini (https://omega-mvc.github.io)
 * @license   https://www.gnu.org/licenses/gpl-3.0-standalone.html     GPL V3.0+
 * @version   2.0.0
 */
interface ApplicationInterface extends ContainerInterface
{
    /**
     * Get instance Application container.
     *
     * @return Application|null Return instance Application container.
     */
    public static function getInstance(): ?Application;

    /**
     * Bootstrap the application using the given bootstrapper classes.
     *
     * @param array<int, class-string> $bootstrappers List of bootstrapper class names.
     * @return void
     */
    public function bootstrapWith(array $bootstrappers): void;

    /**
     * Boot service provider.
     *
     * @return void
     */
    public function bootProvider(): void;

    /**
     * Call the registered booting callbacks.
     *
     * @param callable[] $bootCallBacks Callbacks executed during the booting phase.
     * @return void
     */
    public function callBootCallbacks(array $bootCallBacks): void;

    /**
     * Register a callback to be executed before the application boot process starts.
     *
     * @param callable $callback A callable that will be executed before the boot
     *                           process begins.
     * @return void
     */
    public function bootingCallback(callable $callback): void;

    /**
     * Add booted call back, call after boot is called.
     *
     * @param callable $callback Callback executed after the application has booted.
     * @return void
     */
    public function bootedCallback(callable $callback): void;

    /**
     * Register service provider.
     *
     * @param class-string<AbstractServiceProvider> $provider Class-name service provider
     * @return AbstractServiceProvider The instantiated and registered service provider.
     */
    public function register(string $provider): AbstractServiceProvider;

    /**
     * Registers a callback to be executed when the application terminates.
     *
     * @param callable $terminateCallback The callback to execute on termination.
     * @return $this Returns the application instance for method chaining.
     */
    public function registerTerminate(callable $terminateCallback): self;

    /**
     * Terminate the application.
     *
     * @return void
     */
    public function terminate(): void;

    /**
     * Get the list of core providers.
     *
     * @return array<int, class-string<AbstractServiceProvider>> Registered core service provider class names.
     */
    public function getCoreProviders(): array;

    /**
     * Get the application name.
     *
     * @return string The resolved application name.
     */
    public function getName(): string;

    /**
     * Get the application version string.
     *
     * @return string The resolved application version.
     */
    public function getVersion(): string;

    /**
     * Load the application configuration repository.
     *
     * @param ConfigRepository<string, mixed> $configs The configuration repository to bind.
     * @return void
     */
    public function loadConfig(ConfigRepository $configs): void;

    /**
     * Get the default application bindings and path definitions.
     *
     * @return array<string, mixed> Key-value pairs defining paths, environment, and core settings.
     */
    public function setDefinitions(): array;

    /**
     * Define and register the configuration directory path for the application.
     *
     * @return void
     */
    public function setConfigPath(): void;

    /**
     * Get application (bootstrapper) cache path.
     *
     * @return string Absolute path to the application bootstrap cache directory.
     */
    public function getApplicationCachePath(): string;

    /**
     * Detect application environment.
     *
     * @return string Current application environment (e.g. "dev", "prod").
     */
    public function getEnvironment(): string;

    /**
     * Detect application debug enable.
     *
     * @return bool True when application debug mode is enabled.
     */
    public function isDebugMode(): bool;

    /**
     * Detect application production mode.
     *
     * @return bool True when the application is running in production environment.
     */
    public function isProduction(): bool;

    /**
     * Detect application development mode.
     *
     * @return bool True when the application is running in development environment.
     */
    public function isDev(): bool;

    /**
     * Register aliases to container.
     *
     * @return void
     */
    public function registerAlias(): void;

    /**
     * Determinate application maintenance mode.
     *
     * @return bool True if the application is currently in maintenance mode.
     */
    public function isDownMaintenanceMode(): bool;

    /**
     * Get down maintenance file config.
     *
     * @return array<string, string|int|null> Maintenance mode configuration data.
     */
    public function getDownData(): array;

    /**
     * Abort application to http exception.
     *
     * @param int                   $code    HTTP status code.
     * @param string                $message Exception message.
     * @param array<string, string> $headers HTTP response headers.
     * @return void
     */
    public function abort(int $code, string $message = '', array $headers = []): void;
}
