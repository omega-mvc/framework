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
use PHPUnit\Framework\Attributes\CoversClass;
use RuntimeException;
use Tests\TestCase;

use function Omega\Application\slash;

#[CoversClass(HashServiceProvider::class)]
final class HashServiceProviderTest extends TestCase
{
    protected function tearDown(): void
    {
        AbstractFacade::setFacadeBase(null);
        AbstractFacade::flushInstance();

        if (isset($this->app)) {
            $this->app->flush();
        }

        parent::tearDown();
    }

    public function testRegistersAHashManagerWithBcryptAsTheDefaultDriver(): void
    {
        $this->app = $this->makeApp([]);
        $hash      = $this->appHashManager($this->app);

        $this->assertInstanceOf(BcryptHasher::class, $hash->driver());
    }

    public function testSetsBcryptRoundsFromAnIntegerConfigValue(): void
    {
        $this->app = $this->makeApp(['BCRYPT_ROUNDS' => 10]);
        $bcrypt    = $this->appHashBcrypt($this->app);

        $this->assertSame(10, $bcrypt->info($bcrypt->make('secret'))['options']['cost']);
    }

    public function testDefaultsBcryptRoundsToTwelveWhenConfigIsMissing(): void
    {
        $this->app = $this->makeApp([]);
        $bcrypt    = $this->appHashBcrypt($this->app);

        $this->assertSame(12, $bcrypt->info($bcrypt->make('secret'))['options']['cost']);
    }

    public function testDefaultsBcryptRoundsToTwelveWhenConfigIsNotAnInteger(): void
    {
        $this->app = $this->makeApp(['BCRYPT_ROUNDS' => '10']);
        $bcrypt    = $this->appHashBcrypt($this->app);

        $this->assertSame(12, $bcrypt->info($bcrypt->make('secret'))['options']['cost']);
    }

    public function testRegistersTheArgonAndDefaultHashers(): void
    {
        $this->app = $this->makeApp([]);

        $this->assertInstanceOf(ArgonHasher::class, $this->app->get('hash.argon'));
        $this->assertInstanceOf(Argon2IdHasher::class, $this->app->get('hash.argon2id'));
        $this->assertInstanceOf(DefaultHasher::class, $this->app->get('hash.default'));
    }

    public function testRegistersEveryHashingDriverOnTheManager(): void
    {
        $this->app = $this->makeApp([]);
        $hash      = $this->appHashManager($this->app);

        $this->assertInstanceOf(BcryptHasher::class, $hash->driver('bcrypt'));
        $this->assertInstanceOf(ArgonHasher::class, $hash->driver('argon'));
        $this->assertInstanceOf(Argon2IdHasher::class, $hash->driver('argon2id'));
        $this->assertInstanceOf(DefaultHasher::class, $hash->driver('default'));
    }

    /**
     * Build an application with the hash provider booted.
     *
     * @param array<string, mixed> $config The application configuration.
     * @return Application The configured application instance.
     */
    private function makeApp(array $config): Application
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
    private function appHashManager(Application $app): HashManager
    {
        $hash = $app->get('hash');

        if (!$hash instanceof HashManager) {
            throw new RuntimeException('The "hash" container binding is not a HashManager.');
        }

        return $hash;
    }

    /**
     * Resolve the bcrypt hasher from the application container.
     *
     * @param Application $app The application instance.
     * @return BcryptHasher The resolved bcrypt hasher.
     */
    private function appHashBcrypt(Application $app): BcryptHasher
    {
        $bcrypt = $app->get('hash.bcrypt');

        if (!$bcrypt instanceof BcryptHasher) {
            throw new RuntimeException('The "hash.bcrypt" container binding is not a BcryptHasher.');
        }

        return $bcrypt;
    }
}
