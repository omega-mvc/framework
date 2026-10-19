<?php

declare(strict_types=1);

namespace Tests\Application\Bootstrapper;

use Omega\Application\Application;
use Omega\Application\ApplicationManifest;
use Omega\Application\Bootstrapper\BootProviders;
use Omega\Application\Bootstrapper\RegisterProviders;
use Omega\Config\Bootstrapper\ConfigBootstrapper;
use Omega\Config\ConfigRepository;
use Omega\Container\AbstractServiceProvider;
use Omega\Container\Exceptions\BindingResolutionException;
use Omega\Container\Exceptions\CircularAliasException;
use Omega\Container\Exceptions\EntryNotFoundException;
use PHPUnit\Framework\Attributes\CoversClass;
use ReflectionClass;
use Tests\Application\Bootstrapper\Fixtures\TestRegisterServiceProvider;
use Tests\TestCase;

use function in_array;

#[CoversClass(AbstractServiceProvider::class)]
#[CoversClass(Application::class)]
#[CoversClass(BindingResolutionException::class)]
#[CoversClass(BootProviders::class)]
#[CoversClass(CircularAliasException::class)]
#[CoversClass(EntryNotFoundException::class)]
#[CoversClass(RegisterProviders::class)]
final class RegisterProvidersTest extends TestCase
{
    public function testBootstrapsTheApplicationWithDefaultAndRuntimeProviders(): void
    {
        $app = new Application(__DIR__ . '/../fixtures/support/');
        $app->register(TestRegisterServiceProvider::class);
        $app->bootstrapWith([ConfigBootstrapper::class, BootProviders::class]);

        $this->assertTrue(
            (fn () => $this->{'isBooted'})->call($app),
            'The application should be booted after BootProviders.'
        );
        $this->assertNotEmpty((fn () => $this->{'bootedProviders'})->call($app));

        $loaded = (fn () => $this->{'loadedProviders'})->call($app);
        $this->assertContains(TestRegisterServiceProvider::class, $loaded);
    }

    public function testBootsTheContinueLineInTheBootProvider(): void
    {
        $app = new Application(__DIR__ . '/../fixtures/support/');
        $provider = TestRegisterServiceProvider::class;

        $app->register($provider);

        (fn () => $this->{'bootedProviders'}[] = $provider)->call($app);

        $app->bootstrapWith([ConfigBootstrapper::class, BootProviders::class]);

        $booted = (fn () => $this->{'bootedProviders'})->call($app);

        $this->assertContains($provider, $booted);
    }

    public function testRegistersProvidersFromConfigViaBootstrap(): void
    {
        $app = new Application(__DIR__ . '/../fixtures/support/');

        $app->loadConfig(new ConfigRepository([
            'providers' => [TestRegisterServiceProvider::class],
            'VIEW_EXTENSIONS' => []
        ]));

        $bootstrapper = new RegisterProviders();
        $bootstrapper->bootstrap($app);

        $this->assertTrue(
            self::isProviderLoaded($app, TestRegisterServiceProvider::class),
            'The provider was not loaded correctly.'
        );
    }

    public function testResolvesCoreProvidersWhenConfigHasNoBinding(): void
    {
        $app = new Application(__DIR__ . '/../fixtures/support/');

        $bootstrapper = new RegisterProviders();
        $providers = (fn () => $this->resolveProviders($app))->call($bootstrapper);

        foreach ($app->getCoreProviders() as $core) {
            $this->assertContains($core, $providers);
        }
        $this->assertNotContains(TestRegisterServiceProvider::class, $providers);
    }

    public function testIgnoresConfigBindingThatIsNotConfigRepository(): void
    {
        $app = new Application(__DIR__ . '/../fixtures/support/');
        $app->set('config', static fn () => ['providers' => [TestRegisterServiceProvider::class]]);

        $bootstrapper = new RegisterProviders();
        $providers = (fn () => $this->resolveProviders($app))->call($bootstrapper);

        $this->assertNotContains(TestRegisterServiceProvider::class, $providers);
    }

    public function testIgnoresProvidersConfigEntryThatIsNotArray(): void
    {
        $app = new Application(__DIR__ . '/../fixtures/support/');

        $app->loadConfig(new ConfigRepository([
            'providers' => 'not-an-array',
        ]));

        $bootstrapper = new RegisterProviders();
        $providers = (fn () => $this->resolveProviders($app))->call($bootstrapper);

        $this->assertNotContains(TestRegisterServiceProvider::class, $providers);
    }

    public function testIgnoresPackageProviderListThatIsNotArray(): void
    {
        $app = new Application(__DIR__ . '/../fixtures/support/');
        $app->set(ApplicationManifest::class, static fn () => new class {
            public function providers(): mixed
            {
                return 'not-an-array';
            }
        });

        $bootstrapper = new RegisterProviders();
        $providers = (fn () => $this->resolveProviders($app))->call($bootstrapper);

        $this->assertNotContains(TestRegisterServiceProvider::class, $providers);
    }

    private static function isProviderLoaded(Application $app, string $providerClass): bool
    {
        $reflection = new ReflectionClass($app);
        $property = $reflection->getProperty('loadedProviders');
        $loaded = $property->getValue($app);

        if (!is_array($loaded)) {
            return false;
        }

        return in_array($providerClass, $loaded);
    }
}