<?php

declare(strict_types=1);

namespace Tests\Http;

use Exception;
use Omega\Application\Application;
use Omega\Application\ApplicationManifest;
use Omega\Container\Exceptions\BindingResolutionException;
use Omega\Container\Exceptions\CircularAliasException;
use Omega\Container\Exceptions\EntryNotFoundException;
use Omega\Exceptions\Bootstrapper\HandleExceptions;
use Omega\Exceptions\ExceptionHandler;
use Omega\Http\Exceptions\HttpException;
use Omega\Http\Http;
use Omega\Http\Request;
use Omega\Http\Response;
use Psr\Container\ContainerExceptionInterface;
use ReflectionException;
use Tests\FixturesPathTrait;
use Throwable;

use function is_string;
use function restore_error_handler;
use function restore_exception_handler;

uses(FixturesPathTrait::class);

covers(Application::class);
covers(BindingResolutionException::class);
covers(CircularAliasException::class);
covers(EntryNotFoundException::class);
covers(ExceptionHandler::class);
covers(HttpException::class);
covers(Http::class);
covers(Request::class);
covers(Response::class);
covers(ApplicationManifest::class);

beforeEach(function (): void {
    $this->app = new Application($this->setFixturePath('/fixtures/application-read/'));

    HandleExceptions::resetHandlersState();

    $this->app->set(ApplicationManifest::class, fn () => new ApplicationManifest(
        basePath: is_string($path = $this->app->get('path.base')) ? $path : '',
        applicationCachePath: $this->app->getApplicationCachePath(),
        vendorPath: '/package/'
    ));

    $this->app->set(
        Http::class,
        fn () => new $this->http($this->app)
    );

    $this->app->set(
        ExceptionHandler::class,
        fn () => $this->exceptionHandler
    );

    $this->http = new class ($this->app) extends Http {
        protected function dispatcher(Request $request): array
        {
            throw new HttpException(500, 'Test Exception');
        }
    };

    $this->exceptionHandler = new class ($this->app) extends ExceptionHandler {
        public function render(Request $request, Throwable $th): Response
        {
            return new Response($th->getMessage(), 500);
        }
    };
});

afterEach(function (): void {
    $this->app->flush();

    restore_error_handler();
    restore_exception_handler();
});

it('can render exception', function (): void {
    $http = $this->app->make(Http::class);

    if (!$http instanceof Http) {
        throw new Exception('Expected an Http instance from the container.');
    }

    $response = $http->handle(new Request('/test'));

    expect($response->getContent())->toEqual('Test Exception');
    expect($response->getStatusCode())->toEqual(500);
});