<?php

/**
 * Part of Omega - Tests\Application Package.
 *
 * @link      https://omega-mvc.github.io
 * @author    Adriano Giovannini <agisoftt@gmail.com>
 * @copyright Copyright (c) 2025 - 2026 Adriano Giovannini (https://omega-mvc.github.io)
 * @license   https://www.gnu.org/licenses/gpl-3.0-standalone.html     GPL V3.0+
 * @version   2.0.0
 */

declare(strict_types=1);

namespace Tests\Application;

use Exception;
use Omega\Application\Application;
use Omega\Application\ApplicationInterface;
use Omega\Config\ConfigRepository;
use Omega\Container\Exceptions\BindingResolutionException;
use Omega\Container\Exceptions\CircularAliasException;
use Omega\Container\Exceptions\EntryNotFoundException;
use Omega\Exceptions\ApplicationNotAvailableException;
use Omega\Http\Exceptions\HttpException;
use Omega\Http\Request;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversClassesThatImplementInterface;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerExceptionInterface;
use ReflectionException;
use Tests\FixturesPathTrait;
use Tests\Support\Bootstrap\Support\TestBootstrapProvider;
use Tests\Support\Bootstrap\Support\TestServiceProvider;

/**
 * Integration and behavioral test suite for the Application core.
 *
 * This test class verifies the full lifecycle of the Application instance,
 * including:
 *
 * - Container availability and flushing behavior
 * - Configuration loading (default and custom)
 * - Environment detection and debug mode handling
 * - Macro registration on the HTTP Request
 * - Version resolution
 * - Termination callbacks execution
 * - Abort handling via HTTP exceptions
 * - Bootstrapping and service provider lifecycle
 * - Booting and booted callbacks execution order
 * - Prevention of duplicate service provider registration
 * - Maintenance mode detection and down file handling
 *
 * The goal of this suite is to ensure that the Application behaves
 * correctly as a service container, bootstrapper, and runtime coordinator.
 *
 * @category  Tests
 * @package   Application
 * @link      https://omega-mvc.github.io
 * @author    Adriano Giovannini <agisoftt@gmail.com>
 * @copyright Copyright (c) 2025 - 2026 Adriano Giovannini (https://omega-mvc.github.io)
 * @license   https://www.gnu.org/licenses/gpl-3.0-standalone.html     GPL V3.0+
 * @version   2.0.0
 */
#[CoversClass(Application::class)]
#[CoversClass(ApplicationNotAvailableException::class)]
#[CoversClass(BindingResolutionException::git add .class)]
#[CoversClass(CircularAliasException::class)]
#[CoversClass(EntryNotFoundException::class)]
#[CoversClass(HttpException::class)]
#[CoversClass(Request::class)]
#[CoversClassesThatImplementInterface(ApplicationInterface::class)]
class ApplicationTest extends TestCase
{
    use FixturesPathTrait;

    /**
     * Test it throw error.
     *
     * @return void
     */
    public function testItThrowError(): void
    {
        $this->expectException(ApplicationNotAvailableException::class);
        app();
        app()->flush();
    }

    /**
     * Test it throw error after flush application.
     *
     * @return void
     */
    public function testItThrowErrorAfterFlushApplication(): void
    {
        $app = new Application('/');
        $app->flush();

        $this->expectException(ApplicationNotAvailableException::class);
        app();
        app()->flush();
    }

    /**
     * Test it can load app.
     *
     * @return void
     * @throws BindingResolutionException Thrown when resolving a binding fails.
     * @throws CircularAliasException Thrown when alias resolution loops recursively.
     * @throws ContainerExceptionInterface Thrown on general container errors, e.g., service not retrievable.
     * @throws EntryNotFoundException Thrown when no entry exists for the identifier.
     * @throws ReflectionException Thrown when the requested class or interface cannot be reflected.
     */
    public function testItCanLoadApp(): void
    {
        $app = new Application('');

        $this->assertEquals('/', app()->get('path.base'));

        $app->flush();
    }

    /**
     * Test it can load config from default.
     *
     * @return void
     * @throws BindingResolutionException Thrown when resolving a binding fails.
     * @throws CircularAliasException Thrown when alias resolution loops recursively.
     * @throws ContainerExceptionInterface Thrown on general container errors, e.g., service not retrievable.
     * @throws EntryNotFoundException Thrown when no entry exists for the identifier.
     * @throws ReflectionException Thrown when the requested class or interface cannot be reflected.
     */
    public function testItCanLoadConfigFromDefault(): void
    {
        $app = new Application(__DIR__);

        $data = [
            'BASEURL' => '/',
            'APP_DEBUG' => true,
            'CACHE_STORE' => 'file',
        ];

        $app->loadConfig(new ConfigRepository($data));
        $config = $app->get('config');

        $this->assertEquals($data, $config->getAll());

        $app->flush();
    }

    /**
     * Test it can load environment.
     *
     * @return void
     * @throws BindingResolutionException Thrown when resolving a binding fails.
     * @throws CircularAliasException Thrown when alias resolution loops recursively.
     * @throws ContainerExceptionInterface Thrown on general container errors, e.g., service not retrievable.
     * @throws EntryNotFoundException Thrown when no entry exists for the identifier.
     * @throws Exception Throw when a generic error occured.
     * @throws ReflectionException Thrown when the requested class or interface cannot be reflected.
     */
    public function testItCanLoadEnvironment(): void
    {
        $app = new Application($this->setFixtureBasePath());

        $app->set('environment', 'prod');
        $this->assertFalse($app->isDev());
        $this->assertTrue($app->isProduction());

        $app->set('environment', 'test');
        $this->assertEquals('test', $app->getenvironment());

        $app->set('app.debug', false);
        $this->assertFalse($app->isDebugMode());

        $app->flush();
    }

    /**
     * Test it can call macro request upload.
     *
     * @return void
     */
    public function testItCanCallMacroRequestUploads(): void
    {
        new Application('/');

        $this->assertTrue(Request::hasMacro('upload'));
    }

    /**
     * Test it can call macro request validate.
     *
     * @return void
     */
    public function testItCanCallMacroRequestValidate(): void
    {
        new Application('/');

        $this->assertTrue(Request::hasMacro('validate'));
    }

    /**
     * Test get version return passed version or default.
     *
     * @return void
     */
    public function testGetVersionReturnsPassedVersionOrDefault(): void
    {
        $app = new Application('');

        $this->assertSame('2.0.0', $app->getVersion('2.0.0'));

        $this->assertSame(ApplicationInterface::VERSION, $app->getVersion(null));
    }

    /**
     * Test it can terminate after application done.
     *
     * @return void
     * @throws BindingResolutionException Thrown when resolving a binding fails.
     * @throws CircularAliasException Thrown when alias resolution loops recursively.
     * @throws ContainerExceptionInterface Thrown on general container errors, e.g., service not retrievable.
     * @throws EntryNotFoundException Thrown when no entry exists for the identifier.
     * @throws ReflectionException Thrown when the requested class or interface cannot be reflected.
     */
    public function testItCanTerminateAfterApplicationDone(): void
    {
        $app = new Application('/');
        $app->registerTerminate(static function () {
            echo 'terminated.';
        });
        ob_start();
        echo 'application started.';
        echo 'application ended.';
        $app->terminate();
        $out = ob_get_clean();

        $this->assertEquals('application started.application ended.terminated.', $out);
    }

    /**
     * Test it can abort application.
     *
     * @return void
     */
    public function testItCanAbortApplication(): void
    {
        $this->expectException(HttpException::class);
        new Application(__DIR__)->abort(500);
    }

    /**
     * Test it can bootstrap with.
     *
     * @return void
     * @throws BindingResolutionException Thrown when resolving a binding fails.
     * @throws CircularAliasException Thrown when alias resolution loops recursively.
     * @throws ContainerExceptionInterface Thrown on general container errors, e.g., service not retrievable.
     * @throws EntryNotFoundException Thrown when no entry exists for the identifier.
     * @throws ReflectionException Thrown when the requested class or interface cannot be reflected.
     */
    public function testItCanBootstrapWith(): void
    {
        $app = new Application(__DIR__);

        ob_start();
        $app->bootstrapWith([
            TestBootstrapProvider::class,
        ]);
        $out = ob_get_clean();

        $this->assertEquals('Tests\Support\Bootstrap\Support\TestBootstrapProvider::bootstrap', $out);
        $this->assertTrue($app->bootstrapped);
    }

    /**
     * Test it can add callbacks before and after boot.
     *
     * @return void
     * @throws BindingResolutionException Thrown when resolving a binding fails.
     * @throws CircularAliasException Thrown when alias resolution loops recursively.
     * @throws ContainerExceptionInterface Thrown on general container errors, e.g., service not retrievable.
     * @throws EntryNotFoundException Thrown when no entry exists for the identifier.
     * @throws Exception Throw when a generic error occured.
     * @throws ReflectionException Thrown when the requested class or interface cannot be reflected.
     */
    public function testItCanAddCallBacksBeforeAndAfterBoot(): void
    {
        $app = new Application($this->setFixturePath(slash(path: '/fixtures/application-read/')));

        $app->bootedCallback(static function () {
            echo 'booted01';
        });
        $app->bootedCallback(static function () {
            echo 'booted02';
        });
        $app->bootingCallback(static function () {
            echo 'booting01';
        });
        $app->bootingCallback(static function () {
            echo 'booting02';
        });

        ob_start();
        $app->bootProvider();
        $out = ob_get_clean();

        $this->assertEquals('booting01booting02booted01booted02', $out);
        $this->assertTrue($app->isBooted);
    }

    /**
     * Test it can add call immediately if application already booted.
     *
     * @return void
     * @throws BindingResolutionException Thrown when resolving a binding fails.
     * @throws CircularAliasException Thrown when alias resolution loops recursively.
     * @throws ContainerExceptionInterface Thrown on general container errors, e.g., service not retrievable.
     * @throws EntryNotFoundException Thrown when no entry exists for the identifier.
     * @throws Exception Throw when a generic error occured.
     * @throws ReflectionException Thrown when the requested class or interface cannot be reflected.
     */
    public function testItCanAddCallImmediatelyIfApplicationAlreadyBooted(): void
    {
        $app = new Application($this->setFixturePath(slash(path: '/fixtures/application-read/')));

        $app->bootProvider();

        ob_start();
        $app->bootedCallback(static function () {
            echo 'immediately call';
        });
        $out = ob_get_clean();

        $this->assertTrue($app->isBooted);
        $this->assertEquals('immediately call', $out);
    }

    /**
     * Test it can not duplicate register.
     *
     * @return void
     * @throws BindingResolutionException Thrown when resolving a binding fails.
     * @throws CircularAliasException Thrown when alias resolution loops recursively.
     * @throws ContainerExceptionInterface Thrown on general container errors, e.g., service not retrievable.
     * @throws EntryNotFoundException Thrown when no entry exists for the identifier.
     * @throws ReflectionException Thrown when the requested class or interface cannot be reflected.
     */
    public function testItCanNotDuplicateRegister(): void
    {
        $app = new Application('/');

        $app->set('ping', 'pong');

        $app->register(TestServiceProvider::class);
        $app->register(TestServiceProvider::class);

        $test = $app->get('ping');

        $this->assertEquals('pong', $test);
    }

    /**
     * Test it can get down default.
     *
     * @return void
     * @throws BindingResolutionException Thrown when resolving a binding fails.
     * @throws CircularAliasException Thrown when alias resolution loops recursively.
     * @throws ContainerExceptionInterface Thrown on general container errors, e.g., service not retrievable.
     * @throws EntryNotFoundException Thrown when no entry exists for the identifier.
     * @throws ReflectionException Thrown when the requested class or interface cannot be reflected.
     */
    public function testItCanGetDownDefault(): void
    {
        $app = new Application('/');

        $this->assertEquals([
            'redirect' => null,
            'retry'    => null,
            'status'   => 503,
            'template' => null,
        ], $app->getDownData());
    }

    /**
     * Test it can get down.
     *
     * @return void
     * @throws BindingResolutionException Thrown when resolving a binding fails.
     * @throws CircularAliasException Thrown when alias resolution loops recursively.
     * @throws ContainerExceptionInterface Thrown on general container errors, e.g., service not retrievable.
     * @throws EntryNotFoundException Thrown when no entry exists for the identifier.
     * @throws Exception Throw when a generic error occured.
     * @throws ReflectionException Thrown when the requested class or interface cannot be reflected.
     */
    public function testItCanGetDown(): void
    {
        $app = new Application($this->setFixtureBasePath());
        $app->set('path.storage', $this->setFixturePath(slash(path: '/fixtures/application-read/storage3/')));

        $this->assertEquals([
            'redirect' => null,
            'retry'    => 15,
            'status'   => 503,
            'template' => null,
        ], $app->getDownData());
    }

    /**
     * Test it can detect maintenance mode.
     *
     * @return void
     * @throws BindingResolutionException Thrown when resolving a binding fails.
     * @throws CircularAliasException Thrown when alias resolution loops recursively.
     * @throws ContainerExceptionInterface Thrown on general container errors, e.g., service not retrievable.
     * @throws EntryNotFoundException Thrown when no entry exists for the identifier.
     * @throws Exception Throw when a generic error occured.
     * @throws ReflectionException Thrown when the requested class or interface cannot be reflected.
     */
    public function testItCanDetectMaintenenceMode(): void
    {
        $app = new Application($this->setFixtureBasePath());

        $this->assertFalse($app->isDownMaintenanceMode());

        $app->set('path.storage', $this->setFixturePath(slash(path: '/fixtures/application-read/storage/')));

        $this->assertTrue($app->isDownMaintenanceMode());
    }
}
