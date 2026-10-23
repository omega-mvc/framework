<?php

declare(strict_types=1);

namespace Tests\Application;

use Omega\Application\AbstractApplication;
use Omega\Application\Application;
use Omega\Config\Bootstrapper\ConfigBootstrapper;
use Omega\Config\ConfigRepository;
use Omega\Container\Exceptions\BindingResolutionException;
use Omega\Container\Exceptions\CircularAliasException;
use Omega\Container\Exceptions\EntryNotFoundException;
use Omega\Http\Exceptions\HttpException;
use Omega\Http\Request;
use PHPUnit\Framework\Attributes\CoversClass;
use Tests\Application\Fixtures\TestBootstrapProvider;
use Tests\Application\Fixtures\TestServiceProvider;
use Tests\TestCase;

#[CoversClass(Application::class)]
#[CoversClass(AbstractApplication::class)]
#[CoversClass(BindingResolutionException::class)]
#[CoversClass(CircularAliasException::class)]
#[CoversClass(EntryNotFoundException::class)]
#[CoversClass(HttpException::class)]
#[CoversClass(Request::class)]
final class ApplicationTest extends TestCase
{
    public function testLoadsConfigFromDefault(): void
    {
        $app = new Application(__DIR__);

        $data = [
            'BASEURL'    => '/',
            'APP_DEBUG'  => true,
            'CACHE_STORE' => 'file',
        ];

        $app->loadConfig(new ConfigRepository($data));
        $config = $app->get('config');

        $this->assertInstanceOf(ConfigRepository::class, $config);
        $this->assertSame($data, $config->getAll());

        $app->flush();
    }

    public function testLoadsEnvironment(): void
    {
        $app = new Application(__DIR__);

        $app->set('environment', 'prod');
        $this->assertFalse($app->isDev());
        $this->assertTrue($app->isProduction());

        $app->set('environment', 'test');
        $this->assertSame('test', $app->getenvironment());

        $app->set('app.debug', false);
        $this->assertFalse($app->isDebugMode());

        $app->flush();
    }

    public function testReturnsTheVersionFromConfiguration(): void
    {
        $app = new Application(__DIR__ . '/fixtures/application-read/');

        new ConfigBootstrapper()->bootstrap($app);

        $this->assertSame('2.0.0', $app->getVersion());

        $app->flush();
    }

    public function testTerminatesAfterTheApplicationIsDone(): void
    {
        $app = new Application('/');
        $app->registerTerminate(static function (): void {
            echo 'terminated.';
        });

        ob_start();
        echo 'application started.';
        echo 'application ended.';
        $app->terminate();
        $out = ob_get_clean();

        $this->assertSame('application started.application ended.terminated.', $out);
    }

    public function testAbortsTheApplicationWithAnHttpException(): void
    {
        $this->expectException(HttpException::class);

        new Application(__DIR__)->abort(500);
    }

    public function testBootstrapsProvidersWithBootstrapWith(): void
    {
        $app = new Application(__DIR__);

        ob_start();
        $app->bootstrapWith([
            TestBootstrapProvider::class,
        ]);
        $out = ob_get_clean();

        $this->assertSame('Tests\Application\Fixtures\TestBootstrapProvider::bootstrap', $out);
        $this->assertTrue($app->bootstrapped);
    }

    public function testAddsCallbacksBeforeAndAfterBoot(): void
    {
        $app = new Application(__DIR__ . '/fixtures/application-read/');

        new ConfigBootstrapper()->bootstrap($app);

        $app->bootedCallback(static function (): void {
            echo 'booted01';
        });
        $app->bootedCallback(static function (): void {
            echo 'booted02';
        });
        $app->bootingCallback(static function (): void {
            echo 'booting01';
        });
        $app->bootingCallback(static function (): void {
            echo 'booting02';
        });

        ob_start();
        $app->bootProvider();
        $out = ob_get_clean();

        $this->assertSame('booting01booting02booted01booted02', $out);
        $this->assertTrue($app->isBooted);
    }

    public function testAddsCallbackImmediatelyIfApplicationIsAlreadyBooted(): void
    {
        $app = new Application(__DIR__ . '/fixtures/application-read/');

        new ConfigBootstrapper()->bootstrap($app);

        $app->bootProvider();

        ob_start();
        $app->bootedCallback(static function (): void {
            echo 'immediately call';
        });
        $out = ob_get_clean();

        $this->assertTrue($app->isBooted);
        $this->assertSame('immediately call', $out);
    }

    public function testDoesNotRegisterAProviderTwice(): void
    {
        $app = new Application('/');

        $app->set('ping', 'pong');

        $app->register(TestServiceProvider::class);
        $app->register(TestServiceProvider::class);

        $test = $app->get('ping');

        $this->assertSame('pong', $test);
    }

    public function testReturnsDefaultDownData(): void
    {
        $app = new Application('/');

        $this->assertSame([
            'redirect' => null,
            'retry'    => null,
            'status'   => 503,
            'template' => null,
        ], $app->getDownData());
    }

    public function testReturnsDownDataFromStorage(): void
    {
        $app = new Application(__DIR__);
        $app->set('path.storage', __DIR__ . '/fixtures/application-read/storage3/');

        $this->assertSame([
            'redirect' => null,
            'retry'    => 15,
            'status'   => 503,
            'template' => null,
        ], $app->getDownData());
    }

    public function testDetectsMaintenanceMode(): void
    {
        $app = new Application(__DIR__);

        $this->assertFalse($app->isDownMaintenanceMode());

        $app->set('path.storage', __DIR__ . '/fixtures/application-read/storage/');

        $this->assertTrue($app->isDownMaintenanceMode());
    }

    public function testReturnsFalseWhenStoragePathIsNotAString(): void
    {
        $app = new Application('/');
        $app->set('path.storage', ['array', 'storage']);

        $this->assertFalse($app->isDownMaintenanceMode());
    }

    public function testReturnsDefaultDownDataWhenStoragePathIsNotAString(): void
    {
        $app = new Application('/');
        $app->set('path.storage', ['array', 'storage']);

        $this->assertSame([
            'redirect' => null,
            'retry'    => null,
            'status'   => 503,
            'template' => null,
        ], $app->getDownData());
    }

    public function testTerminatesWithNoCallbacks(): void
    {
        $this->expectNotToPerformAssertions();

        $app = new Application('/');

        $app->terminate();
    }
}
