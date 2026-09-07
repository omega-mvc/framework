<?php

declare(strict_types=1);

namespace Tests\Facades;

use Omega\Application\Application;
use Omega\Cache\Facade\Cache;
use Omega\Collection\Collection;
use Omega\Config\ConfigRepository;
use Omega\Config\Facade\Config;
use Omega\Container\Exceptions\CircularAliasException;
use Omega\Cron\Facade\Schedule;
use Omega\Database\ConnectionInterface;
use Omega\Database\DatabaseManager;
use Omega\Database\Facades\DB;
use Omega\Database\Facades\PDO;
use Omega\Database\Facades\Schema;
use Omega\Database\Query\Table;
use Omega\Facade\AbstractFacade;
use Omega\Facade\Exceptions\FacadeObjectNotSetException;
use Omega\Security\Facade\Hash;
use Omega\Security\Hashing\HashManager;
use Omega\View\Facades\View;
use Omega\View\Facades\Vite;
use ReflectionProperty;
use Tests\Facades\Sample\FacadesTestClass;
use Tests\Facades\Support\NullFacade;
use Tests\Facades\Support\TestAbstractFacade;
use Tests\FixturesPathTrait;

use function expect;

covers(AbstractFacade::class);
covers(Application::class);
covers(DB::class);
covers(DatabaseManager::class);
covers(Cache::class);
covers(CircularAliasException::class);
covers(Collection::class);
covers(Config::class);
covers(ConfigRepository::class);
covers(FacadeObjectNotSetException::class);
covers(Hash::class);
covers(HashManager::class);
covers(PDO::class);
covers(Schedule::class);
covers(Schema::class);
covers(Table::class);
covers(View::class);
covers(Vite::class);

uses(FixturesPathTrait::class);

afterEach(function (): void {
    AbstractFacade::setFacadeBase(null);
    AbstractFacade::flushInstance();
});

it('can call static', function (): void {
    $app = new Application($this->setFixtureBasePath());
    $app->set(Collection::class, fn () => new Collection(['php' => 'greater']));

    AbstractFacade::setFacadeBase($app);

    expect(FacadesTestClass::has('php'))->toBeTrue();
    $app->flush();
    AbstractFacade::flushInstance();
});

it('throws when application is not set', function (): void {
    AbstractFacade::flushInstance();
    AbstractFacade::setFacadeBase(null);

    FacadesTestClass::has('php');
})->throws(FacadeObjectNotSetException::class, 'has not been set');

it('constructor sets application', function (): void {
    $app = new Application($this->setFixtureBasePath());

    new TestAbstractFacade($app);

    $ref = new ReflectionProperty(AbstractFacade::class, 'app');
    $ref->setAccessible(true);

    expect($ref->getValue())->toBe($app);
});

it('throws when app is not set', function (): void {
    NullFacade::has('php');
})->throws(FacadeObjectNotSetException::class);

it('uses cached instance', function (): void {
    $app = new Application($this->setFixtureBasePath());

    $app->set(Collection::class, fn () => new Collection(['php' => 'greater']));

    AbstractFacade::setFacadeBase($app);

    expect(FacadesTestClass::has('php'))->toBeTrue();
    expect(FacadesTestClass::has('php'))->toBeTrue();
});

it('returns the correct accessor', function (string $facade, string $accessor): void {
    expect($facade::getFacadeAccessor())->toBe($accessor);
})->with([
    [DB::class, DatabaseManager::class],
    [Config::class, ConfigRepository::class],
    [Cache::class, 'cache'],
    [Hash::class, HashManager::class],
    [PDO::class, 'database'],
    [Schedule::class, 'schedule'],
    [Schema::class, 'Schema'],
    [View::class, 'view.instance'],
    [Vite::class, 'vite.gets'],
]);

it('table returns query builder', function (): void {
    $app = new Application($this->setFixtureBasePath());

    $connection = $this->createStub(ConnectionInterface::class);
    $connection->method('getInstance')->willReturn($connection);

    $app->set('database', fn () => $connection);

    AbstractFacade::setFacadeBase($app);

    $table = DB::table('users');

    expect($table)->toBeInstanceOf(Table::class);
});

it('from returns query builder', function (): void {
    $connection = $this->createStub(ConnectionInterface::class);

    $table = DB::from('users', $connection);

    expect($table)->toBeInstanceOf(Table::class);
});