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

use BadMethodCallException;
use Exception;
use Omega\Config\ConfigRepository;
use Omega\Container\Container;
use Omega\Container\Exceptions\BindingResolutionException;
use Omega\Container\Exceptions\CircularAliasException;
use Omega\Container\Exceptions\EntryNotFoundException;
use Omega\Container\Provider\AbstractServiceProvider;
use Omega\Http\Exceptions\HttpException;
use Omega\Http\Request;
use Omega\Support\AddonServiceProvider;
use Omega\Support\PackageManifest;
use Omega\Support\Vite;
use Omega\View\Templator;
use Psr\Container\ContainerExceptionInterface;
use ReflectionException;

use function assert;
use function count;
use function file_exists;
use function in_array;
use function rtrim;
use function str_ends_with;
use function str_replace;
use function str_starts_with;
use function strtolower;
use function substr;

use const DIRECTORY_SEPARATOR;

/**
 * Core application container.
 *
 * Manages configuration, service providers, bootstrapping,
 *
 * @category  Omega
 * @package   Application
 * @link      https://omega-mvc.github.io
 * @author    Adriano Giovannini <agisoftt@gmail.com>
 * @copyright Copyright (c) 2025 - 2026 Adriano Giovannini (https://omega-mvc.github.io)
 * @license   https://www.gnu.org/licenses/gpl-3.0-standalone.html     GPL V3.0+
 * @version   2.0.0
 */
abstract class AbstractApplication extends Container implements ApplicationInterface
{
    /** @var Application|null Currently active Application runtime instance. */
    protected static ?Application $app = null;

    /** @var string Absolute base path of the application root directory. */
    protected string $basePath;

    /** @var array<int, class-string<AbstractServiceProvider>>|null Registered service provider class names. */
    protected ?array $providers = [];

    /** @var AbstractServiceProvider[] Service providers that have completed the boot phase. */
    protected array $bootedProviders = [];

    /** @var AbstractServiceProvider[] Service providers that have been registered. */
    protected array $loadedProviders = [];

    /** @var bool Indicates whether the application has completed the boot phase. */
    public bool $isBooted = false { // phpcs:ignore
        get {
            return $this->isBooted; // phpcs:ignore
        }
    }

    /** @var bool Indicates whether the application bootstrap process has completed. */
    private bool $isBootstrapped = false;

    /** Indicates whether the application has finished bootstrapping. */
    public bool $bootstrapped { // phpcs:ignore
        get => $this->isBootstrapped; // phpcs:ignore
    }

    /** @var callable[] Callbacks executed when the application is terminating. */
    private array $terminateCallback = [];

    /** @var callable[] Callbacks executed before service providers are booted. */
    protected array $bootingCallbacks = [];

    /** @var callable[] Callbacks executed after all service providers are booted. */
    protected array $bootedCallbacks = [];

    /**
     * Application constructor.
     *
     * @param string|null $basePath Base application path.
     * @return void
     * @throws Exception
     */
    public function __construct(?string $basePath = null)
    {
        $this->basePath = str_replace('/', DIRECTORY_SEPARATOR, $basePath);

        $this->set('path.base', $this->basePath . DIRECTORY_SEPARATOR);

        $this->setConfigPath();

        $this->setBaseBinding();

        $this->register(AddonServiceProvider::class);

        $this->registerAlias();

        foreach ($this->definitions() as $key => $value) {
            $this->set($key, $value);
        }
    }

    /**
     * {@inheritdoc}
     */
    abstract public function definitions(): array;

    /**
     * {@inheritdoc}
     */
    abstract public function getVersion(?string $version): string;

    /**
     * {@inheritdoc}
     */
    abstract public function setConfigPath(): void;

    /**
     * Get instance Application container.
     *
     * @return Application|null Return instance Application container.
     */
    public static function getInstance(): ?Application
    {
        return Application::$app;
    }

    /**
     * Register the base application bindings and finalize application identity.
     *
     * This method is the **single point of truth** where the Application instance
     * becomes globally available and fully integrated with the container.
     *
     * Responsibilities:
     * - Defines this instance as the active Application runtime.
     * - Registers core container bindings (app, Application::class, Container::class).
     * - Initializes framework-level services that depend on a stable Application instance.
     *
     * Contract:
     * - MUST be called exactly once during application construction.
     * - MUST be executed before any service providers, helpers, or container lookups
     *   that rely on the Application instance.
     * - After this method executes, the Application instance is considered
     *   globally addressable and safe to use.
     *
     * Rationale:
     * The framework relies on a globally accessible Application reference for
     * container resolution, helpers, service providers, and testing utilities.
     * Assigning the instance here guarantees a deterministic initialization order.
     *
     * @return void
     * @throws CircularAliasException Thrown when alias resolution loops recursively.
     */
    protected function setBaseBinding(): void
    {
        assert(
            Application::$app === null,
            'Application::$app must be null before base bindings are registered.'
        );

        // The Application instance must be globally available before any container
        // bindings, helpers, or service providers are resolved.
        Application::$app = $this;

        $this->set('app', $this);
        $this->set(Application::class, $this);
        $this->set(Container::class, $this);

        $this->set(
            PackageManifest::class,
            fn () => new PackageManifest(
                $this->basePath,
                $this->getApplicationCachePath()
            )
        );
    }

    /**
     * {@inheritdoc}
     *
     * @throws CircularAliasException Thrown when alias resolution loops recursively.
     */
    public function loadConfig(ConfigRepository $configs): void
    {
        $this->set('config', fn (): ConfigRepository => $configs);

        $this->set('config.view.extensions', $configs['VIEW_EXTENSIONS']);
        $this->providers = $configs['providers'];
    }

    /**
     * {@inheritdoc}
     */
    abstract public function getApplicationCachePath(): string;

    /**
     * {@inheritdoc}
     *
     * @throws BindingResolutionException Thrown when resolving a binding fails.
     * @throws CircularAliasException Thrown when alias resolution loops recursively.
     * @throws ContainerExceptionInterface Thrown on general container errors, e.g., service not retrievable.
     * @throws EntryNotFoundException Thrown when no entry exists for the identifier.
     * @throws ReflectionException Thrown when the requested class or interface cannot be reflected.
     */
    public function getEnvironment(): string
    {
        return $this->get('environment');
    }

    /**
     * {@inheritdoc}
     *
     * @throws BindingResolutionException Thrown when resolving a binding fails.
     * @throws CircularAliasException Thrown when alias resolution loops recursively.
     * @throws ContainerExceptionInterface Thrown on general container errors, e.g., service not retrievable.
     * @throws EntryNotFoundException Thrown when no entry exists for the identifier.
     * @throws ReflectionException Thrown when the requested class or interface cannot be reflected.
     */
    public function isDebugMode(): bool
    {
        return $this->get('app.debug');
    }

    /**
     * {@inheritdoc}
     *
     * @throws BindingResolutionException Thrown when resolving a binding fails.
     * @throws CircularAliasException Thrown when alias resolution loops recursively.
     * @throws ContainerExceptionInterface Thrown on general container errors, e.g., service not retrievable.
     * @throws EntryNotFoundException Thrown when no entry exists for the identifier.
     * @throws ReflectionException Thrown when the requested class or interface cannot be reflected.
     */
    public function isProduction(): bool
    {
        return $this->getEnvironment() === 'prod';
    }

    /**
     * {@inheritdoc}
     *
     * @throws BindingResolutionException Thrown when resolving a binding fails.
     * @throws CircularAliasException Thrown when alias resolution loops recursively.
     * @throws ContainerExceptionInterface Thrown on general container errors, e.g., service not retrievable.
     * @throws EntryNotFoundException Thrown when no entry exists for the identifier.
     * @throws ReflectionException Thrown when the requested class or interface cannot be reflected.
     */
    public function isDev(): bool
    {
        return $this->getEnvironment() === 'dev';
    }

    /**
     * {@inheritdoc}
     *
     * @throws BindingResolutionException Thrown when resolving a binding fails.
     * @throws CircularAliasException Thrown when alias resolution loops recursively.
     * @throws ContainerExceptionInterface Thrown on general container errors, e.g., service not retrievable.
     * @throws EntryNotFoundException Thrown when no entry exists for the identifier.
     * @throws ReflectionException Thrown when the requested class or interface cannot be reflected.
     */
    public function bootstrapWith(array $bootstrappers): void
    {
        $this->isBootstrapped = true;

        foreach ($bootstrappers as $bootstrapper) {
            $this->make($bootstrapper)->bootstrap($this);
        }
    }

    /**
     * {@inheritdoc}
     *
     * @throws BindingResolutionException Thrown when resolving a binding fails.
     * @throws ContainerExceptionInterface Thrown on general container errors, e.g., service not retrievable.
     * @throws EntryNotFoundException Thrown when no entry exists for the identifier.
     * @throws ReflectionException Thrown when the requested class or interface cannot be reflected.
     */
    public function bootProvider(): void
    {
        if ($this->isBooted) {
            return;
        }

        $this->callBootCallbacks($this->bootingCallbacks);

        foreach ($this->getMergeProviders() as $provider) {
            if (in_array($provider, $this->bootedProviders)) {
                continue;
            }

            $this->call([$provider, 'boot']);
            $this->bootedProviders[] = $provider;
        }

        $this->callBootCallbacks($this->bootedCallbacks);

        $this->isBooted = true;
    }

    /**
     * {@inheritdoc}
     *
     * @throws BindingResolutionException Thrown when resolving a binding fails.
     * @throws ContainerExceptionInterface Thrown on general container errors, e.g., service not retrievable.
     * @throws EntryNotFoundException Thrown when no entry exists for the identifier.
     * @throws ReflectionException Thrown when the requested class or interface cannot be reflected.
     */
    public function registerProvider(): void
    {
        foreach ($this->getMergeProviders() as $provider) {
            if (in_array($provider, $this->loadedProviders)) {
                continue;
            }

            $this->call([$provider, 'register']);

            $this->loadedProviders[] = $provider;
        }
    }

    /**
     * {@inheritdoc}
     *
     * @throws BindingResolutionException Thrown when resolving a binding fails.
     * @throws ContainerExceptionInterface Thrown on general container errors, e.g., service not retrievable.
     * @throws EntryNotFoundException Thrown when no entry exists for the identifier.
     * @throws ReflectionException Thrown when the requested class or interface cannot be reflected.
     */
    public function callBootCallbacks(array $bootCallBacks): void
    {
        $index = 0;

        while ($index < count($bootCallBacks)) {
            $this->call($bootCallBacks[$index]);

            $index++;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function bootingCallback(callable $callback): void
    {
        $this->bootingCallbacks[] = $callback;
    }

    /**
     * {@inheritdoc}
     *
     * @throws BindingResolutionException Thrown when resolving a binding fails.
     * @throws ContainerExceptionInterface Thrown on general container errors, e.g., service not retrievable.
     * @throws EntryNotFoundException Thrown when no entry exists for the identifier.
     * @throws ReflectionException Thrown when the requested class or interface cannot be reflected.
     */
    public function bootedCallback(callable $callback): void
    {
        $this->bootedCallbacks[] = $callback;

        if ($this->isBooted) {
            $this->call($callback);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function flush(): void
    {
        Application::$app = null;

        $this->providers         = [];
        $this->loadedProviders   = [];
        $this->bootedProviders   = [];
        $this->terminateCallback = [];
        $this->bootingCallbacks  = [];
        $this->bootedCallbacks   = [];

        parent::flush();
    }

    /**
     * {@inheritdoc}
     */
    public function register(string $provider): AbstractServiceProvider
    {
        $providerClassName = $provider;
        $provider          = new $provider($this);

        $provider->register();
        $this->loadedProviders[] = $providerClassName;

        if ($this->isBooted) {
            $provider->boot();
            $this->bootedProviders[] = $providerClassName;
        }


        $this->providers[] = $providerClassName;

        return $provider;
    }

    /**
     * Registers a callback to be executed when the application terminates.
     *
     * This method allows you to add one or more terminating callbacks that
     * will be called after the application finishes handling a request.
     *
     * @param callable $terminateCallback The callback to execute on termination.
     * @return $this Returns the application instance for method chaining.
     */
    public function registerTerminate(callable $terminateCallback): self
    {
        $this->terminateCallback[] = $terminateCallback;

        return $this;
    }

    /**
     * {@inheritdoc}
     *
     * @throws BindingResolutionException Thrown when resolving a binding fails.
     * @throws ContainerExceptionInterface Thrown on general container errors, e.g., service not retrievable.
     * @throws EntryNotFoundException Thrown when no entry exists for the identifier.
     * @throws ReflectionException Thrown when the requested class or interface cannot be reflected.
     */
    public function terminate(): void
    {
        $index = 0;

        while ($index < count($this->terminateCallback)) {
            $this->call($this->terminateCallback[$index]);

            $index++;
        }
    }

    /**
     * {@inheritdoc}
     *
     * @throws BindingResolutionException Thrown when resolving a binding fails.
     * @throws CircularAliasException Thrown when alias resolution loops recursively.
     * @throws ContainerExceptionInterface Thrown on general container errors, e.g., service not retrievable.
     * @throws EntryNotFoundException Thrown when no entry exists for the identifier.
     * @throws ReflectionException Thrown when the requested class or interface cannot be reflected.
     */
    abstract public function isDownMaintenanceMode(): bool;

    /**
     * {@inheritdoc}
     *
     * @throws BindingResolutionException Thrown when resolving a binding fails.
     * @throws CircularAliasException Thrown when alias resolution loops recursively.
     * @throws ContainerExceptionInterface Thrown on general container errors, e.g., service not retrievable.
     * @throws EntryNotFoundException Thrown when no entry exists for the identifier.
     * @throws ReflectionException Thrown when the requested class or interface cannot be reflected.
     */
    public function getDownData(): array
    {
        $default = [
            'redirect' => null,
            'retry'    => null,
            'status'   => 503,
            'template' => null,
        ];

        if (false === file_exists($down = get_path('path.storage') . 'app/down')) {
            return $default;
        }

        /** @var array<string, string|int|null> $config */
        $config = include $down;

        foreach ($config as $key => $value) {
            $default[$key] = $value;
        }

        return $default;
    }

    /**
     * {@inheritdoc}
     */
    public function abort(int $code, string $message = '', array $headers = []): void
    {
        throw new HttpException($code, $message, null, $headers);
    }

    /**
     * Register aliases to container.
     *
     * @return void
     * @throws Exception Thrown when alias registration fails.
     */
    protected function registerAlias(): void
    {
        foreach (
            [
            'request'       => [Request::class],
            'view.instance' => [Templator::class],
            'vite.gets'     => [Vite::class],
            'config'        => [ConfigRepository::class],
            ] as $abstract => $aliases
        ) {
            foreach ($aliases as $alias) {
                $this->alias($abstract, $alias);
            }
        }
    }

    /**
     * Merge application provider and vendor package provider.
     *
     * @return AbstractServiceProvider[] Merged list of application and package service providers.
     * @throws BindingResolutionException Thrown when resolving a binding fails.
     * @throws CircularAliasException Thrown when alias resolution loops recursively.
     * @throws ContainerExceptionInterface Thrown on general container errors, e.g., service not retrievable.
     * @throws EntryNotFoundException Thrown when no entry exists for the identifier.
     * @throws ReflectionException Thrown when the requested class or interface cannot be reflected.
     */
    protected function getMergeProviders(): array
    {
        $packageProviders = $this->make(PackageManifest::class)->providers() ?? [];

        return [
            ...$this->providers,
            ...$packageProviders,
        ];
    }
}
