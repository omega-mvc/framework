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
use PHPUnit\Framework\Attributes\CoversClass;
use Tests\TestCase;
use Throwable;

use function is_string;

#[CoversClass(Application::class)]
#[CoversClass(BindingResolutionException::class)]
#[CoversClass(CircularAliasException::class)]
#[CoversClass(EntryNotFoundException::class)]
#[CoversClass(ExceptionHandler::class)]
#[CoversClass(HttpException::class)]
#[CoversClass(Http::class)]
#[CoversClass(Request::class)]
#[CoversClass(Response::class)]
#[CoversClass(ApplicationManifest::class)]
final class KernelHandleExceptionTest extends TestCase
{
    private Http $http;

    private ExceptionHandler $exceptionHandler;

    protected function setUp(): void
    {
        parent::setUp();

        $this->app = new Application(__DIR__ . '/fixtures/application-read/');

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
    }

    protected function tearDown(): void
    {
        $this->app->flush();

        parent::tearDown();
    }

    public function testCanRenderException(): void
    {
        $http = $this->app->make(Http::class);

        if (!$http instanceof Http) {
            throw new Exception('Expected an Http instance from the container.');
        }

        $response = $http->handle(new Request('/test'));

        $this->assertEquals('Test Exception', $response->getContent());
        $this->assertEquals(500, $response->getStatusCode());
    }
}
