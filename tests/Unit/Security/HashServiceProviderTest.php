<?php

declare(strict_types=1);

namespace Tests\Security;

use Omega\Application\Application;
use Omega\Config\ConfigRepository;
use Omega\Facade\AbstractFacade;
use Omega\Security\HashServiceProvider;
use Omega\Security\Hashing\Argon2IdHasher;
use Omega\Security\Hashing\ArgonHasher;
use Omega\Security\Hashing\BcryptHasher;
use Omega\Security\Hashing\DefaultHasher;
use Omega\Security\Hashing\HashManager;

use function Omega\Application\slash;

covers(HashServiceProvider::class);

afterEach(function (): void {
    AbstractFacade::setFacadeBase(null);
    AbstractFacade::flushInstance();

    if (isset($this->app)) {
        $this->app->flush();
    }
});

it('registers a hash manager with bcrypt as the default driver', function (): void {
    $this->app = makeApp([]);
    $hash      = appHashManager($this->app);

    expect($hash->driver())->toBeInstanceOf(BcryptHasher::class);
});

it('sets bcrypt rounds from an integer config value', function (): void {
    $this->app = makeApp(['BCRYPT_ROUNDS' => 10]);
    $bcrypt    = appHashBcrypt($this->app);

    expect($bcrypt->info($bcrypt->make('secret'))['options']['cost'])->toBe(10);
});

it('defaults bcrypt rounds to twelve when config is missing', function (): void {
    $this->app = makeApp([]);
    $bcrypt    = appHashBcrypt($this->app);

    expect($bcrypt->info($bcrypt->make('secret'))['options']['cost'])->toBe(12);
});

it('defaults bcrypt rounds to twelve when config is not an integer', function (): void {
    $this->app = makeApp(['BCRYPT_ROUNDS' => '10']);
    $bcrypt    = appHashBcrypt($this->app);

    expect($bcrypt->info($bcrypt->make('secret'))['options']['cost'])->toBe(12);
});

it('registers the argon and default hashers', function (): void {
    $this->app = makeApp([]);

    expect($this->app->get('hash.argon'))->toBeInstanceOf(ArgonHasher::class);
    expect($this->app->get('hash.argon2id'))->toBeInstanceOf(Argon2IdHasher::class);
    expect($this->app->get('hash.default'))->toBeInstanceOf(DefaultHasher::class);
});

it('registers every hashing driver on the manager', function (): void {
    $this->app = makeApp([]);
    $hash      = appHashManager($this->app);

    expect($hash->driver('bcrypt'))->toBeInstanceOf(BcryptHasher::class);
    expect($hash->driver('argon'))->toBeInstanceOf(ArgonHasher::class);
    expect($hash->driver('argon2id'))->toBeInstanceOf(Argon2IdHasher::class);
    expect($hash->driver('default'))->toBeInstanceOf(DefaultHasher::class);
});

/**
 * Build an application with the hash provider booted.
 *
 * @param array<string, mixed> $config The application configuration.
 * @return Application The configured application instance.
 */
function makeApp(array $config): Application
{
    $app = new Application(slash(__DIR__ . '/fixtures/support/'));
    AbstractFacade::setFacadeBase($app);
    $app->set('config', static fn () => new ConfigRepository($config));

    (new HashServiceProvider($app))->boot();

    return $app;
}

/**
 * Resolve the hash manager from the application container.
 *
 * @param Application $app The application instance.
 * @return HashManager The resolved hash manager.
 */
function appHashManager(Application $app): HashManager
{
    $hash = $app->get('hash');

    if (!$hash instanceof HashManager) {
        throw new \RuntimeException('The "hash" container binding is not a HashManager.');
    }

    return $hash;
}

/**
 * Resolve the bcrypt hasher from the application container.
 *
 * @param Application $app The application instance.
 * @return BcryptHasher The resolved bcrypt hasher.
 */
function appHashBcrypt(Application $app): BcryptHasher
{
    $bcrypt = $app->get('hash.bcrypt');

    if (!$bcrypt instanceof BcryptHasher) {
        throw new \RuntimeException('The "hash.bcrypt" container binding is not a BcryptHasher.');
    }

    return $bcrypt;
}