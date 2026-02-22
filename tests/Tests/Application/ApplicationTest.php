<?php

declare(strict_types=1);

namespace Tests\Application;

use Omega\Application\Application;
use Omega\Container\Exceptions\BindingResolutionException;
use Omega\Container\Exceptions\CircularAliasException;
use Omega\Container\Exceptions\EntryNotFoundException;
use Omega\Exceptions\ApplicationNotAvailableException;
use Omega\Http\Exceptions\HttpException;
use Omega\Http\Request;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversFunction;
use PHPUnit\Framework\TestCase;
use Tests\FixturesPathTrait;
use Tests\Support\Bootstrap\Support\TestBootstrapProvider;

#[CoversClass(Application::class)]
#[CoversClass(ApplicationNotAvailableException::class)]
#[CoversClass(BindingResolutionException::class)]
#[CoversClass(CircularAliasException::class)]
#[CoversClass(EntryNotFoundException::class)]
class ApplicationTest extends TestCase
{
    use FixturesPathTrait;

    /** @test */
    public function testItThrowError(): void
    {
        $this->expectException(ApplicationNotAvailableException::class);
        app();
        app()->flush();
    }

    /** @test */
    public function testItThrowErrorAfterFlushApplication(): void
    {
        $app = new Application('/');
        $app->flush();

        $this->expectException(ApplicationNotAvailableException::class);
        app();
        app()->flush();
    }

    /** @test */
    public function testItCanLoadApp(): void
    {
        $app = new Application('');

        $this->assertEquals('/', app()->get('path.base'));

        $app->flush();
    }

    /** @test */
    public function testItCanCallMacroRequestValidate(): void
    {
        new Application('/');

        $this->assertTrue(Request::hasMacro('validate'));
    }

    /**
     * @test
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

    /** @test */
    public function testItCanAbortApplication(): void
    {
        $this->expectException(HttpException::class);
        new Application(__DIR__)->abort(500);
    }

    /** @test */
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

    /** @test */
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
}
