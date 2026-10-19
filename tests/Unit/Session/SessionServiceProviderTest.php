<?php

declare(strict_types=1);

namespace Tests\Session;

use Omega\Application\Application;
use Omega\Application\ApplicationInterface;
use Omega\Cache\Storage\MemoryStorage;
use Omega\Config\ConfigRepository;
use Omega\Database\DatabaseManager;
use Omega\Middleware\StartSessionMiddleware;
use Omega\Session\Exceptions\UnknownDriverException;
use Omega\Session\SessionManager;
use Omega\Session\SessionServiceProvider;
use Omega\Session\Storage\ArrayStorage;
use Omega\Session\Storage\CacheStorage;
use Omega\Session\Storage\DatabaseStorage;
use Omega\Session\Storage\NativeStorage;
use PHPUnit\Framework\Attributes\CoversClass;
use ReflectionMethod;
use RuntimeException;
use Tests\TestCase;

use function Omega\Application\slash;

#[CoversClass(SessionServiceProvider::class)]
final class SessionServiceProviderTest extends TestCase
{
    protected function tearDown(): void
    {
        if (isset($this->app)) {
            $this->app->flush();
        }

        parent::tearDown();
    }

    public function testRegistersASessionManagerWithTheConfiguredDefaultDriver(): void
    {
        $config = [
            'session' => [
                'default' => 'array',
                'drivers' => [
                    'array' => [],
                ],
            ],
        ];

        $this->app = $this->makeApp($config);
        $session = $this->appSessionManager($this->app);

        $this->assertInstanceOf(ArrayStorage::class, $session->getDriver());
    }

    public function testRegistersEveryConfiguredStorageDriver(): void
    {
        $config = [
            'session' => [
                'default' => 'array',
                'drivers' => [
                    'array'    => [],
                    'native'   => [],
                    'cache'    => [],
                    'database' => [],
                ],
            ],
        ];

        $this->app = $this->makeApp($config);
        $this->app->set('cache', new MemoryStorage([]));
        $this->app->set(DatabaseManager::class, new DatabaseManager([]));

        $this->assertInstanceOf(ArrayStorage::class, $this->app->get('session.storage.array'));
        $this->assertInstanceOf(NativeStorage::class, $this->app->get('session.storage.native'));
        $this->assertInstanceOf(CacheStorage::class, $this->app->get('session.storage.cache'));
        $this->assertInstanceOf(DatabaseStorage::class, $this->app->get('session.storage.database'));
    }

    public function testRegistersNonDefaultDriversOnTheSessionManager(): void
    {
        $config = [
            'session' => [
                'default' => 'array',
                'drivers' => [
                    'array'    => [],
                    'native'   => [],
                    'cache'    => [],
                    'database' => [],
                ],
            ],
        ];

        $this->app = $this->makeApp($config);
        $this->app->set('cache', new MemoryStorage([]));
        $this->app->set(DatabaseManager::class, new DatabaseManager([]));

        $session = $this->appSessionManager($this->app);

        $this->assertInstanceOf(ArrayStorage::class, $session->getDriver());

        $resolve = new ReflectionMethod(SessionManager::class, 'resolve');

        $this->assertInstanceOf(NativeStorage::class, $resolve->invoke($session, 'native'));
        $this->assertInstanceOf(CacheStorage::class, $resolve->invoke($session, 'cache'));
        $this->assertInstanceOf(DatabaseStorage::class, $resolve->invoke($session, 'database'));
    }

    public function testDefaultsToTheNativeDriverWhenNoSessionConfigurationIsPresent(): void
    {
        $this->app = $this->makeApp(['environment' => 'testing']);

        $session = $this->appSessionManager($this->app);

        $this->assertInstanceOf(NativeStorage::class, $session->getDriver());
    }

    public function testAppliesDriverOptionsToTheCacheAndDatabaseStorage(): void
    {
        $config = [
            'session' => [
                'default' => 'array',
                'drivers' => [
                    'array'    => [],
                    'cache'    => ['prefix' => 'store_'],
                    'database' => ['table' => 'user_sessions'],
                ],
            ],
        ];

        $this->app = $this->makeApp($config);
        $this->app->set('cache', new MemoryStorage([]));
        $this->app->set(DatabaseManager::class, new DatabaseManager([]));

        $this->assertInstanceOf(CacheStorage::class, $this->app->get('session.storage.cache'));
        $this->assertInstanceOf(DatabaseStorage::class, $this->app->get('session.storage.database'));
    }

    public function testRegistersTheStartSessionMiddleware(): void
    {
        $this->app = $this->makeApp(['session' => ['default' => 'array', 'drivers' => ['array' => []]]]);

        $this->assertInstanceOf(
            StartSessionMiddleware::class,
            $this->app->get(StartSessionMiddleware::class)
        );
    }

    public function testThrowsWhenTheRegisteredSessionIsNotAManager(): void
    {
        $this->app = $this->makeApp(['session' => ['default' => 'array', 'drivers' => ['array' => []]]]);
        $this->app->set('session', new \stdClass());

        $this->expectException(UnknownDriverException::class);
        $this->expectExceptionMessageIsOrContains(
            'The session storage driver "session" could not be resolved or is not registered.'
        );

        $this->app->get(StartSessionMiddleware::class);
    }

    public function testThrowsWhenTheConfiguredDefaultDriverIsUnsupported(): void
    {
        $this->app = $this->makeApp(['session' => ['default' => 'bogus']]);

        $this->expectException(UnknownDriverException::class);
        $this->expectExceptionMessageIsOrContains(
            'The session storage driver "bogus" could not be resolved or is not registered.'
        );

        $this->app->get('session');
    }

    public function testThrowsWhenAConfiguredStorageDriverIsUnsupported(): void
    {
        $config = [
            'session' => [
                'default' => 'array',
                'drivers' => [
                    'array' => [],
                    'bogus' => [],
                ],
            ],
        ];

        $this->app = $this->makeApp($config);

        $this->expectException(UnknownDriverException::class);
        $this->expectExceptionMessageIsOrContains(
            'The session storage driver "bogus" could not be resolved or is not registered.'
        );

        $this->app->get('session.storage.bogus');
    }

    public function testThrowsWhenTheCacheServiceIsNotACacheImplementation(): void
    {
        $config = [
            'session' => [
                'default' => 'cache',
                'drivers' => [
                    'cache' => [],
                ],
            ],
        ];

        $this->app = $this->makeApp($config);
        $this->app->set('cache', new \stdClass());

        $this->expectException(UnknownDriverException::class);
        $this->expectExceptionMessageIsOrContains(
            'The session storage driver "cache" could not be resolved or is not registered.'
        );

        $this->app->get('session');
    }

    public function testThrowsWhenTheDatabaseServiceIsNotAConnectionImplementation(): void
    {
        $config = [
            'session' => [
                'default' => 'database',
                'drivers' => [
                    'database' => [],
                ],
            ],
        ];

        $this->app = $this->makeApp($config);
        $this->app->set(DatabaseManager::class, new \stdClass());

        $this->expectException(UnknownDriverException::class);
        $this->expectExceptionMessageIsOrContains(
            'The session storage driver "database" could not be resolved or is not registered.'
        );

        $this->app->get('session');
    }

    /**
     * Build an application with the session provider booted.
     *
     * @param array<string, mixed> $config The application configuration.
     * @return Application The configured application instance.
     */
    private function makeApp(array $config): Application
    {
        $app = new Application(slash(__DIR__ . '/fixtures/support/'));
        $app->set('config', static fn (): ConfigRepository => new ConfigRepository($config));

        (new SessionServiceProvider($app))->boot();

        return $app;
    }

    /**
     * Resolve the session manager from the application container.
     *
     * @param ApplicationInterface $app The application instance.
     * @return SessionManager The resolved session manager.
     */
    private function appSessionManager(ApplicationInterface $app): SessionManager
    {
        $session = $app->get('session');

        if (!$session instanceof SessionManager) {
            throw new RuntimeException('The "session" container binding is not a SessionManager.');
        }

        return $session;
    }
}
