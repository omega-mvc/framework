<?php

declare(strict_types=1);

namespace Tests\Exceptions\Bootstrap;

use ErrorException;
use Omega\Application\Application;
use Omega\Container\Exceptions\BindingResolutionException;
use Omega\Container\Exceptions\CircularAliasException;
use Omega\Container\Exceptions\EntryNotFoundException;
use Omega\Exceptions\Bootstrapper\HandleExceptions;
use Omega\Exceptions\ExceptionHandler;
use Omega\Http\Request;
use Tests\Exceptions\Bootstrap\Fixtures\TestHandleExceptions;
use Tests\Exceptions\Bootstrap\Fixtures\TestLog;
use Throwable;
use PHPUnit\Framework\Attributes\After;
use PHPUnit\Framework\Attributes\AfterClass;
use PHPUnit\Framework\Attributes\Before;
use PHPUnit\Framework\Attributes\BeforeClass;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(Application::class)]
#[CoversClass(BindingResolutionException::class)]
#[CoversClass(CircularAliasException::class)]
#[CoversClass(EntryNotFoundException::class)]
#[CoversClass(ExceptionHandler::class)]
#[CoversClass(Request::class)]
#[CoversClass(HandleExceptions::class)]
final class HandleExceptionsTest extends TestCase
{
    protected function tearDown(): void
    {
        HandleExceptions::resetHandlersState();
    }

    #[Test]
    public function test_can_handle_an_error(): void
    {
        $app = new Application(basePath: __DIR__ . '/fixtures');
        $app->set('environment', 'testing');

        $handle = new HandleExceptions();
        $handle->bootstrap($app);

        $this->expectException(ErrorException::class);
        $handle->handleError(E_ERROR, __NAMESPACE__ . '\HandleExceptionsTest', __FILE__, __LINE__);
        $app->flush();
    }

    #[Test]
    public function test_can_handle_a_deprecation_error(): void
    {
        $app = new Application(basePath: __DIR__ . '/fixtures');
        $app->set('environment', 'testing');
        $app->set(ExceptionHandler::class, fn () => new TestHandleExceptions($app));
        $app->set('logging', fn () => new TestLog());

        $handle = new HandleExceptions();
        $handle->bootstrap($app);

        $result = $handle->handleError(E_USER_DEPRECATED, 'deprecation', __FILE__, __LINE__);
        $this->assertTrue($result);
        $app->flush();
    }

    #[Test]
    public function test_can_handle_an_exception(): void
    {
        $app = new Application(basePath: __DIR__ . '/fixtures');
        $app->set('request', fn (): Request => new Request('/'));
        $app->set('environment', 'testing');
        $app->set(ExceptionHandler::class, fn () => new TestHandleExceptions($app));

        $handle = new HandleExceptions();
        $handle->bootstrap($app);

        try {
            throw new ErrorException('testing');
        } catch (Throwable $th) {
            $handle->handleException($th);
        }

        $app->flush();
    }

    #[Test]
    public function test_skips_shutdown_handling_within_the_phpunit_runtime(): void
    {
        $this->markTestSkipped('Shutdown behavior cannot be tested within PHPUnit runtime.');
    }
}
