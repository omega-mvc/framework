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
use ReflectionClass;
use Tests\Application\Bootstrapper\Fixtures\TestRegisterServiceProvider;
use Tests\FixturesPathTrait;

use function in_array;

covers(AbstractServiceProvider::class);
covers(Application::class);
covers(BindingResolutionException::class);
covers(BootProviders::class);
covers(CircularAliasException::class);
covers(EntryNotFoundException::class);
covers(RegisterProviders::class);

uses(FixturesPathTrait::class);

it('bootstraps the application with default and runtime providers', function (): void {
    $app = new Application($this->setFixturePath('/fixtures/support/'));
    $app->register(TestRegisterServiceProvider::class);
    $app->bootstrapWith([ConfigBootstrapper::class, BootProviders::class]);

    expect((fn () => $this->{'isBooted'})->call($app))->toBeTrue('The application should be booted after BootProviders.');
    expect((fn () => $this->{'bootedProviders'})->call($app))->not->toBeEmpty();

    $loaded = (fn () => $this->{'loadedProviders'})->call($app);
    expect($loaded)->toBeArray();
    expect($loaded)->toContain(TestRegisterServiceProvider::class);
});

it('boots the continue line in the boot provider', function (): void {
    $app = new Application($this->setFixturePath('/fixtures/support/'));
    $provider = TestRegisterServiceProvider::class;

    $app->register($provider);

    (fn () => $this->{'bootedProviders'}[] = $provider)->call($app);

    $app->bootstrapWith([ConfigBootstrapper::class, BootProviders::class]);

    $booted = (fn () => $this->{'bootedProviders'})->call($app);

    expect($booted)->toBeArray();
    expect($booted)->toContain($provider);
});

it('registers providers from the config via bootstrap', function (): void {
    $app = new Application($this->setFixturePath('/fixtures/support/'));

    $app->loadConfig(new ConfigRepository([
        'providers' => [TestRegisterServiceProvider::class],
        'VIEW_EXTENSIONS' => []
    ]));

    $bootstrapper = new RegisterProviders();
    $bootstrapper->bootstrap($app);

    expect(isProviderLoaded($app, TestRegisterServiceProvider::class))->toBeTrue('The provider was not loaded correctly.');
});

it('resolves core providers when the config has no binding', function (): void {
    $app = new Application($this->setFixturePath('/fixtures/support/'));

    $bootstrapper = new RegisterProviders();
    $providers = (fn () => $this->resolveProviders($app))->call($bootstrapper);

    expect($providers)->toBeArray();
    foreach ($app->getCoreProviders() as $core) {
        expect($providers)->toContain($core);
    }
    expect($providers)->not->toContain(TestRegisterServiceProvider::class);
});

it('ignores a config binding that is not a ConfigRepository', function (): void {
    $app = new Application($this->setFixturePath('/fixtures/support/'));
    $app->set('config', static fn () => ['providers' => [TestRegisterServiceProvider::class]]);

    $bootstrapper = new RegisterProviders();
    $providers = (fn () => $this->resolveProviders($app))->call($bootstrapper);

    expect($providers)->toBeArray();
    expect($providers)->not->toContain(TestRegisterServiceProvider::class);
});

it('ignores a providers config entry that is not an array', function (): void {
    $app = new Application($this->setFixturePath('/fixtures/support/'));
    $app->loadConfig(new ConfigRepository([
        'providers' => 'not-an-array',
    ]));

    $bootstrapper = new RegisterProviders();
    $providers = (fn () => $this->resolveProviders($app))->call($bootstrapper);

    expect($providers)->toBeArray();
    expect($providers)->not->toContain(TestRegisterServiceProvider::class);
});

it('ignores a package provider list that is not an array', function (): void {
    $app = new Application($this->setFixturePath('/fixtures/support/'));
    $app->set(ApplicationManifest::class, static fn () => new class {
        public function providers(): mixed
        {
            return 'not-an-array';
        }
    });

    $bootstrapper = new RegisterProviders();
    $providers = (fn () => $this->resolveProviders($app))->call($bootstrapper);

    expect($providers)->toBeArray();
    expect($providers)->not->toContain(TestRegisterServiceProvider::class);
});

function isProviderLoaded(Application $app, string $providerClass): bool
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