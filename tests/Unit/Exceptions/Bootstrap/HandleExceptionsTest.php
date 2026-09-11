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

covers(Application::class);
covers(BindingResolutionException::class);
covers(CircularAliasException::class);
covers(EntryNotFoundException::class);
covers(ExceptionHandler::class);
covers(Request::class);
covers(HandleExceptions::class);

afterEach(fn () => HandleExceptions::resetHandlersState());

it('can handle an error', function (): void {
    $app = new Application(basePath: __DIR__ . '/fixtures');
    $app->set('environment', 'testing');

    $handle = new HandleExceptions();
    $handle->bootstrap($app);

    expect(fn () => $handle->handleError(E_ERROR, __NAMESPACE__ . '\HandleExceptionsTest', __FILE__, __LINE__))
        ->toThrow(ErrorException::class, __NAMESPACE__ . '\HandleExceptionsTest');

    $app->flush();
});

it('can handle a deprecation error', function (): void {
    $app = new Application(basePath: __DIR__ . '/fixtures');
    $app->set('environment', 'testing');
    $app->set(ExceptionHandler::class, fn () => new TestHandleExceptions($app));
    $app->set('logging', fn () => new TestLog());

    $handle = new HandleExceptions();
    $handle->bootstrap($app);

    $result = $handle->handleError(E_USER_DEPRECATED, 'deprecation', __FILE__, __LINE__);
    expect($result)->toBeTrue();

    $app->flush();
});

it('can handle an exception', function (): void {
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
});

it('skips shutdown handling within the phpunit runtime', function (): void {
    $this->markTestSkipped('Shutdown behavior cannot be tested within PHPUnit runtime.');
});