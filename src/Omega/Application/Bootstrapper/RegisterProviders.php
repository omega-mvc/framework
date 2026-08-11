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

namespace Omega\Application\Bootstrapper;

use Omega\Application\ApplicationInterface;
use Omega\Container\AbstractServiceProvider;
use Omega\Container\Exceptions\BindingResolutionException;
use Omega\Container\Exceptions\CircularAliasException;
use Omega\Container\Exceptions\EntryNotFoundException;
use Omega\Application\ApplicationManifest;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use ReflectionException;

/**
 * Handles the registration phase of the application’s service providers.
 *
 * This bootstrapper is responsible for invoking the provider registration
 * mechanism on the Application instance, ensuring that all providers defined
 * by the framework or the user are properly loaded before the application
 * lifecycle proceeds.
 *
 * It acts as an initialization step during the bootstrapping sequence,
 * preparing the container and related bindings required by the framework
 * to operate correctly.
 *
 * @category   Omega
 * @package    Application
 * @subpackage Bootstrapper
 * @link       https://omega-mvc.github.io
 * @author     Adriano Giovannini <agisoftt@gmail.com>
 * @copyright  Copyright (c) 2025 - 2026 Adriano Giovannini (https://omega-mvc.github.io)
 * @license    https://www.gnu.org/licenses/gpl-3.0-standalone.html     GPL V3.0+
 * @version    2.0.0
 */
class RegisterProviders
{
    /**
     * Bootstrap all service providers in the given application instance.
     *
     * @param ApplicationInterface $app The application instance used to resolve provider sources.
     * @return void
     * @throws BindingResolutionException Thrown when resolving a binding fails.
     * @throws CircularAliasException Thrown when alias resolution loops recursively.
     * @throws ContainerExceptionInterface Thrown on general container errors, e.g., service not retrievable.
     * @throws EntryNotFoundException Thrown when no entry exists for the identifier.
     * @throws NotFoundExceptionInterface Thrown when no entry exists for the requested identifier.
     * @throws ReflectionException Thrown when the requested class or interface cannot be reflected.
     */
    public function bootstrap(ApplicationInterface $app): void
    {
        foreach ($this->resolveProviders($app) as $provider) {
            $app->register($provider);
        }
    }

    /**
     * Resolve all service providers registered by the application.
     *
     * This method collects service provider class names from multiple sources:
     * core application providers, configuration-defined providers, and package
     * providers discovered through the application manifest.
     *
     * Duplicate providers are removed before returning the final list, ensuring
     * that each provider is registered only once during the application bootstrap
     * process.
     *
     * @param ApplicationInterface $app The application instance used to resolve provider sources.
     * @return array<int, class-string<AbstractServiceProvider>> The list of unique service provider class names.
     * @throws BindingResolutionException Thrown when resolving a binding fails.
     * @throws CircularAliasException Thrown when alias resolution loops recursively.
     * @throws ContainerExceptionInterface Thrown on general container errors, e.g., service not retrievable.
     * @throws EntryNotFoundException Thrown when no entry exists for the identifier.
     * @throws NotFoundExceptionInterface Thrown when no entry exists for the requested identifier.
     * @throws ReflectionException Thrown when the requested class or interface cannot be reflected.
     */
    private function resolveProviders(ApplicationInterface $app): array
    {
        $configProviders = [];

        if ($app->has('config')) {
            $configProviders = $app->get('config')
                ->get('app.providers', []);
        }

        $packageProviders = $app
            ->make(ApplicationManifest::class)
            ->providers() ?? [];

        return array_unique([
            ...$app->getCoreProviders(),
            ...$configProviders,
            ...$packageProviders,
        ]);
    }
}
