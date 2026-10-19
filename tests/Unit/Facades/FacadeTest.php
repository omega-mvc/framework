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
use Omega\Session\Facade\Session;
use Omega\Session\SessionManager;
use Omega\Session\Storage\ArrayStorage;
use Omega\View\Facades\View;
use Omega\View\Facades\Vite;
use PHPUnit\Framework\Attributes\CoversClass;
use ReflectionMethod;
use ReflectionProperty;
use Tests\Facades\Sample\FacadesTestClass;
use Tests\Facades\Support\NullFacade;
use Tests\Facades\Support\TestAbstractFacade;
use Tests\TestCase;

#[CoversClass(AbstractFacade::class)]
#[CoversClass(Application::class)]
#[CoversClass(DB::class)]
#[CoversClass(DatabaseManager::class)]
#[CoversClass(Cache::class)]
#[CoversClass(CircularAliasException::class)]
#[CoversClass(Collection::class)]
#[CoversClass(Config::class)]
#[CoversClass(ConfigRepository::class)]
#[CoversClass(FacadeObjectNotSetException::class)]
#[CoversClass(Hash::class)]
#[CoversClass(HashManager::class)]
#[CoversClass(PDO::class)]
#[CoversClass(Schedule::class)]
#[CoversClass(Schema::class)]
#[CoversClass(Session::class)]
#[CoversClass(SessionManager::class)]
#[CoversClass(ArrayStorage::class)]
#[CoversClass(Table::class)]
#[CoversClass(View::class)]
#[CoversClass(Vite::class)]
final class FacadeTest extends TestCase
{
    protected function tearDown(): void
    {
        AbstractFacade::setFacadeBase(null);
        AbstractFacade::flushInstance();

        parent::tearDown();
    }

    public function testCanCallStatic(): void
    {
        $app = new Application(__DIR__);
        $app->set(Collection::class, fn () => new Collection(['php' => 'greater']));

        AbstractFacade::setFacadeBase($app);

        $this->assertTrue(FacadesTestClass::has('php'));
        $app->flush();
        AbstractFacade::flushInstance();
    }

    public function testThrowsWhenApplicationIsNotSet(): void
    {
        AbstractFacade::flushInstance();
        AbstractFacade::setFacadeBase(null);

        $this->expectException(FacadeObjectNotSetException::class);
        $this->expectExceptionMessage('has not been set');

        FacadesTestClass::has('php');
    }

    public function testConstructorSetsApplication(): void
    {
        $app = new Application(__DIR__);

        new TestAbstractFacade($app);

        $ref = new ReflectionProperty(AbstractFacade::class, 'app');

        $this->assertSame($app, $ref->getValue());
    }

    public function testThrowsWhenAppIsNotSet(): void
    {
        $this->expectException(FacadeObjectNotSetException::class);

        NullFacade::has('php');
    }

    public function testThrowsFromGetFacadeBaseWhenTheApplicationIsNotSet(): void
    {
        AbstractFacade::flushInstance();
        AbstractFacade::setFacadeBase(null);

        $method = new ReflectionMethod(AbstractFacade::class, 'getFacadeBase');

        $this->expectException(FacadeObjectNotSetException::class);

        $method->invoke(null, 'missing.service');
    }

    public function testUsesCachedInstance(): void
    {
        $app = new Application(__DIR__);

        $app->set(Collection::class, fn () => new Collection(['php' => 'greater']));

        AbstractFacade::setFacadeBase($app);

        $this->assertTrue(FacadesTestClass::has('php'));
        $this->assertTrue(FacadesTestClass::has('php'));
    }

    public function testReturnsTheCorrectAccessor(): void
    {
        $cases = [
            DB::class        => DatabaseManager::class,
            Config::class    => ConfigRepository::class,
            Cache::class     => 'cache',
            Hash::class      => HashManager::class,
            PDO::class       => 'database',
            Schedule::class  => 'schedule',
            Schema::class    => 'Schema',
            Session::class   => 'session',
            View::class      => 'view.instance',
            Vite::class      => 'vite.gets',
        ];

        foreach ($cases as $facade => $accessor) {
            $this->assertSame($accessor, $facade::getFacadeAccessor());
        }
    }

    public function testForwardsStaticCallsToTheSessionManager(): void
    {
        $app = new Application(__DIR__);
        $app->set('session', fn () => new SessionManager('array', new ArrayStorage()));

        AbstractFacade::setFacadeBase($app);

        $this->assertTrue(Session::start());

        $app->flush();
        AbstractFacade::flushInstance();
    }

    public function testTableReturnsQueryBuilder(): void
    {
        $app = new Application(__DIR__);

        $connection = $this->createStub(ConnectionInterface::class);
        $connection->method('getInstance')->willReturn($connection);

        $app->set('database', fn () => $connection);

        AbstractFacade::setFacadeBase($app);

        $first  = DB::table('users');
        $second = DB::table('users');

        $this->assertNotSame($first, $second);
    }

    public function testFromReturnsQueryBuilder(): void
    {
        $connection = $this->createStub(ConnectionInterface::class);

        $first  = DB::from('users', $connection);
        $second = DB::from('users', $connection);

        $this->assertNotSame($first, $second);
    }
}
