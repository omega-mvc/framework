<?php

/**
 * Part of Omega - Tests\Session Package.
 * @link https://omega-mvc.github.io
 * @author Adriano Giovannini <agisoftt@gmail.com>
 * @copyright Copyright (c) 2025 - 2026 Adriano Giovannini (https://omega-mvc.github.io)
 * @license https://www.gnu.org/licenses/gpl-3.0-standalone.html     GPL V3.0+
 * @version 2.0.0
 */

declare(strict_types=1);

namespace Tests\Session;

use Omega\Application\Application;
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
use ReflectionMethod;

use function Omega\Application\slash;

covers(SessionServiceProvider::class);

afterEach(function (): void {
    if (isset($this->app)) {
        $this->app->flush();
    }
});

it('registers a session manager with the configured default driver', function (): void {
    $config = [
        'session' => [
            'default' => 'array',
            'drivers' => [
                'array' => [],
            ],
        ],
    ];

    $this->app = makeApp($config);
    $session = $this->app->get('session');

    expect($session)->toBeInstanceOf(SessionManager::class);
    expect($session->getDriver())->toBeInstanceOf(ArrayStorage::class);
});

it('registers every configured storage driver', function (): void {
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

    $this->app = makeApp($config);
    $this->app->set('cache', new MemoryStorage([]));
    $this->app->set(DatabaseManager::class, new DatabaseManager([]));

    expect($this->app->get('session.storage.array'))->toBeInstanceOf(ArrayStorage::class);
    expect($this->app->get('session.storage.native'))->toBeInstanceOf(NativeStorage::class);
    expect($this->app->get('session.storage.cache'))->toBeInstanceOf(CacheStorage::class);
    expect($this->app->get('session.storage.database'))->toBeInstanceOf(DatabaseStorage::class);
});

it('registers non default drivers on the session manager', function (): void {
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

    $this->app = makeApp($config);
    $this->app->set('cache', new MemoryStorage([]));
    $this->app->set(DatabaseManager::class, new DatabaseManager([]));

    $session = $this->app->get('session');

    expect($session->getDriver())->toBeInstanceOf(ArrayStorage::class);

    $resolve = new ReflectionMethod(SessionManager::class, 'resolve');

    expect($resolve->invoke($session, 'native'))->toBeInstanceOf(NativeStorage::class);
    expect($resolve->invoke($session, 'cache'))->toBeInstanceOf(CacheStorage::class);
    expect($resolve->invoke($session, 'database'))->toBeInstanceOf(DatabaseStorage::class);
});

it('defaults to the native driver when no session configuration is present', function (): void {
    $this->app = makeApp(['environment' => 'testing']);

    $session = $this->app->get('session');

    expect($session)->toBeInstanceOf(SessionManager::class);
    expect($session->getDriver())->toBeInstanceOf(NativeStorage::class);
});

it('applies driver options to the cache and database storage', function (): void {
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

    $this->app = makeApp($config);
    $this->app->set('cache', new MemoryStorage([]));
    $this->app->set(DatabaseManager::class, new DatabaseManager([]));

    expect($this->app->get('session.storage.cache'))->toBeInstanceOf(CacheStorage::class);
    expect($this->app->get('session.storage.database'))->toBeInstanceOf(DatabaseStorage::class);
});

it('registers the start session middleware', function (): void {
    $this->app = makeApp(['session' => ['default' => 'array', 'drivers' => ['array' => []]]]);

    expect($this->app->get(StartSessionMiddleware::class))->toBeInstanceOf(StartSessionMiddleware::class);
});

it('throws when the registered session is not a manager', function (): void {
    $this->app = makeApp(['session' => ['default' => 'array', 'drivers' => ['array' => []]]]);
    $this->app->set('session', new \stdClass());

    $this->app->get(StartSessionMiddleware::class);
})->throws(UnknownDriverException::class, 'The session storage driver "session" could not be resolved or is not registered.');

it('throws when the configured default driver is unsupported', function (): void {
    $this->app = makeApp(['session' => ['default' => 'bogus']]);

    $this->app->get('session');
})->throws(UnknownDriverException::class, 'The session storage driver "bogus" could not be resolved or is not registered.');

it('throws when a configured storage driver is unsupported', function (): void {
    $config = [
        'session' => [
            'default' => 'array',
            'drivers' => [
                'array' => [],
                'bogus' => [],
            ],
        ],
    ];

    $this->app = makeApp($config);

    $this->app->get('session.storage.bogus');
})->throws(UnknownDriverException::class, 'The session storage driver "bogus" could not be resolved or is not registered.');

it('throws when the cache service is not a cache implementation', function (): void {
    $config = [
        'session' => [
            'default' => 'cache',
            'drivers' => [
                'cache' => [],
            ],
        ],
    ];

    $this->app = makeApp($config);
    $this->app->set('cache', new \stdClass());

    $this->app->get('session');
})->throws(UnknownDriverException::class, 'The session storage driver "cache" could not be resolved or is not registered.');

it('throws when the database service is not a connection implementation', function (): void {
    $config = [
        'session' => [
            'default' => 'database',
            'drivers' => [
                'database' => [],
            ],
        ],
    ];

    $this->app = makeApp($config);
    $this->app->set(DatabaseManager::class, new \stdClass());

    $this->app->get('session');
})->throws(UnknownDriverException::class, 'The session storage driver "database" could not be resolved or is not registered.');

/**
 * Build an application with the session provider booted.
 *
 * @param array<string, mixed> $config The application configuration.
 * @return Application The configured application instance.
 */
function makeApp(array $config): Application
{
    $app = new Application(slash(__DIR__ . '/fixtures/support/'));
    $app->set('config', static fn () => new ConfigRepository($config));

    (new SessionServiceProvider($app))->boot();

    return $app;
}