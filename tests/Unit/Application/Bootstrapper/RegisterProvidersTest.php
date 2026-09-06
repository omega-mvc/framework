<?php

/**
 * Part of Omega - Tests\Application\Bootstrap Package.
 *
 * @link      https://omega-mvc.github.io
 * @author    Adriano Giovannini <agisoftt@gmail.com>
 * @copyright Copyright (c) 2025 - 2026 Adriano Giovannini (https://omega-mvc.github.io)
 * @license   https://www.gnu.org/licenses/gpl-3.0-standalone.html     GPL V3.0+
 * @version   2.0.0
 */

declare(strict_types=1);

namespace Tests\Application\Bootstrapper;

use Exception;
use Omega\Application\Application;
use Omega\Config\Bootstrapper\ConfigBootstrapper;
use Omega\Config\ConfigRepository;
use Omega\Container\Exceptions\BindingResolutionException;
use Omega\Container\Exceptions\CircularAliasException;
use Omega\Container\Exceptions\EntryNotFoundException;
use Omega\Container\AbstractServiceProvider;
use Omega\Application\ApplicationManifest;
use Omega\Application\Bootstrapper\BootProviders;
use Omega\Application\Bootstrapper\RegisterProviders;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerExceptionInterface;
use ReflectionClass;
use ReflectionException;
use Tests\Application\Bootstrapper\Fixtures\TestRegisterServiceProvider;
use Tests\FixturesPathTrait;

use function in_array;

/**
 * Class RegisterProvidersTest
 *
 * This test suite verifies that service providers can be correctly registered
 * and booted within the Application lifecycle. It ensures that providers added
 * at runtime are included in the boot sequence alongside default and vendor
 * providers, and that the final list of booted providers reflects all expected
 * entries.
 *
 * @category   Tests
 * @package    Application
 * @subpackage Bootstrap
 * @link       https://omega-mvc.github.io
 * @author     Adriano Giovannini <agisoftt@gmail.com>
 * @copyright  Copyright (c) 2025 - 2026 Adriano Giovannini (https://omega-mvc.github.io)
 * @license    https://www.gnu.org/licenses/gpl-3.0-standalone.html     GPL V3.0+
 * @version    2.0.0
 */
#[CoversClass(AbstractServiceProvider::class)]
#[CoversClass(Application::class)]
#[CoversClass(BindingResolutionException::class)]
#[CoversClass(BootProviders::class)]
#[CoversClass(CircularAliasException::class)]
#[CoversClass(EntryNotFoundException::class)]
#[CoversClass(RegisterProviders::class)]
final class RegisterProvidersTest extends TestCase
{
    use FixturesPathTrait;

    /**
     * Test bootstrap.
     *
     * @return void
     * @throws BindingResolutionException Thrown when resolving a binding fails.
     * @throws CircularAliasException Thrown when alias resolution loops recursively.
     * @throws ContainerExceptionInterface Thrown on general container errors, e.g., service not retrievable.
     * @throws EntryNotFoundException Thrown when no entry exists for the identifier.
     * @throws Exception if a generic error occurred
     * @throws ReflectionException Thrown when the requested class or interface cannot be reflected.
     */
    public function testBootstrap(): void
    {
        $app = new Application($this->setFixturePath('/fixtures/support/'));
        $app->register(TestRegisterServiceProvider::class);
        $app->bootstrapWith([ConfigBootstrapper::class, BootProviders::class]);

        $this->assertTrue(
            (fn () => $this->{'isBooted'})->call($app),
            'The application should be booted after BootProviders.'
        );
        $this->assertNotEmpty(
            (fn () => $this->{'bootedProviders'})->call($app),
            'The default core providers should be booted.'
        );
        $loaded = (fn () => $this->{'loadedProviders'})->call($app);
        $this->assertIsArray($loaded);
        $this->assertContains(
            TestRegisterServiceProvider::class,
            $loaded,
            'The provider registered before boot should be loaded.'
        );
    }

    /**
     * Test boot provider continue line is covered.
     *
     * @return void
     * @throws BindingResolutionException Thrown when resolving a binding fails.
     * @throws CircularAliasException Thrown when alias resolution loops recursively.
     * @throws ContainerExceptionInterface Thrown on general container errors, e.g., service not retrievable.
     * @throws EntryNotFoundException Thrown when no entry exists for the identifier.
     * @throws Exception if a generic error occurred
     * @throws ReflectionException Thrown when the requested class or interface cannot be reflected.
     */
    public function testBootProviderContinueLineIsCovered(): void
    {
        $app = new Application($this->setFixturePath('/fixtures/support/'));
        $provider = TestRegisterServiceProvider::class;

        $app->register($provider);

        (fn () => $this->{'bootedProviders'}[] = $provider)->call($app);

        $app->bootstrapWith([ConfigBootstrapper::class, BootProviders::class]);

        $booted = (fn () => $this->{'bootedProviders'})->call($app);

        $this->assertIsArray($booted);
        $this->assertContains($provider, $booted);
    }

    /**
     * Test bootstrap register providers.
     *
     * @return void
     * @throws BindingResolutionException Thrown when resolving a binding fails.
     * @throws CircularAliasException Thrown when alias resolution loops recursively.
     * @throws ContainerExceptionInterface Thrown on general container errors, e.g., service not retrievable.
     * @throws EntryNotFoundException Thrown when no entry exists for the identifier.
     * @throws Exception if a generic error occurred
     * @throws ReflectionException Thrown when the requested class or interface cannot be reflected.
     */
    public function testBootstrapRegistersProviders(): void
    {
        $app = new Application($this->setFixturePath('/fixtures/support/'));

        $app->loadConfig(new ConfigRepository([
            'providers' => [TestRegisterServiceProvider::class],
            'VIEW_EXTENSIONS' => []
        ]));

        $bootstrapper = new RegisterProviders();
        $bootstrapper->bootstrap($app);

        $this->assertTrue(
            $this->isProviderLoaded($app, TestRegisterServiceProvider::class),
            'The provider was not loaded correctly.'
        );
    }

    /**
     * Test that resolving providers with no config binding yields core providers only.
     *
     * @return void
     * @throws Exception if a generic error occurred
     */
    public function testResolveProvidersWithoutConfigBinding(): void
    {
        $app = new Application($this->setFixturePath('/fixtures/support/'));

        $bootstrapper = new RegisterProviders();
        $providers = (fn () => $this->resolveProviders($app))->call($bootstrapper);

        $this->assertIsArray($providers);
        foreach ($app->getCoreProviders() as $core) {
            $this->assertContains($core, $providers);
        }
        $this->assertNotContains(TestRegisterServiceProvider::class, $providers);
    }

    /**
     * Test that a config binding which is not a ConfigRepository is ignored.
     *
     * @return void
     * @throws Exception if a generic error occurred
     */
    public function testResolveConfigProvidersWithNonRepositoryConfig(): void
    {
        $app = new Application($this->setFixturePath('/fixtures/support/'));
        $app->set('config', static fn () => ['providers' => [TestRegisterServiceProvider::class]]);

        $bootstrapper = new RegisterProviders();
        $providers = (fn () => $this->resolveProviders($app))->call($bootstrapper);

        $this->assertIsArray($providers);
        $this->assertNotContains(TestRegisterServiceProvider::class, $providers);
    }

    /**
     * Test that a config 'providers' entry that is not an array is ignored.
     *
     * @return void
     * @throws Exception if a generic error occurred
     */
    public function testResolveConfigProvidersWithNonArrayProviders(): void
    {
        $app = new Application($this->setFixturePath('/fixtures/support/'));
        $app->loadConfig(new ConfigRepository([
            'providers' => 'not-an-array',
        ]));

        $bootstrapper = new RegisterProviders();
        $providers = (fn () => $this->resolveProviders($app))->call($bootstrapper);

        $this->assertIsArray($providers);
        $this->assertNotContains(TestRegisterServiceProvider::class, $providers);
    }

    /**
     * Test that a package provider list that is not an array is ignored.
     *
     * @return void
     * @throws Exception if a generic error occurred
     */
    public function testResolvePackageProvidersWithNonArrayList(): void
    {
        $app = new Application($this->setFixturePath('/fixtures/support/'));
        $app->set(ApplicationManifest::class, static fn () => new class {
            public function providers(): mixed
            {
                return 'not-an-array';
            }
        });

        $bootstrapper = new RegisterProviders();
        $providers = (fn () => $this->resolveProviders($app))->call($bootstrapper);

        $this->assertIsArray($providers);
        $this->assertNotContains(TestRegisterServiceProvider::class, $providers);
    }

    /**
     * Check if providers is loaded.
     *
     * @param Application $app           The Application instance.
     * @param string      $providerClass The providers class name.
     * @return bool Return true if the providers is loaded, false if not.
     */
    private function isProviderLoaded(Application $app, string $providerClass): bool
    {
        $reflection = new ReflectionClass($app);
        $property = $reflection->getProperty('loadedProviders');
        $property->setAccessible(true);
        $loaded = $property->getValue($app);

        if (!is_array($loaded)) {
            return false;
        }

        return in_array($providerClass, $loaded);
    }
}
