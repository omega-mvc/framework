<?php

declare(strict_types=1);

namespace Tests\Http;

use Exception;
use Omega\Application\Application;
use Omega\Container\Exceptions\BindingResolutionException;
use Omega\Container\Exceptions\CircularAliasException;
use Omega\Container\Exceptions\EntryNotFoundException;
use Omega\Http\Http;
use Omega\Http\Request;
use Omega\Http\Response;
use PHPUnit\Framework\Attributes\CoversClass;
use Tests\Http\Support\TestKernelTerminate;
use Tests\TestCase;

use function ob_get_clean;
use function ob_start;

#[CoversClass(Application::class)]
#[CoversClass(BindingResolutionException::class)]
#[CoversClass(CircularAliasException::class)]
#[CoversClass(EntryNotFoundException::class)]
#[CoversClass(Http::class)]
#[CoversClass(Request::class)]
#[CoversClass(Response::class)]
final class KernelTerminateTest extends TestCase
{
    private Http $http;

    protected function setUp(): void
    {
        parent::setUp();

        $this->app = new Application(__DIR__);

        $this->app->set(
            Http::class,
            fn () => new $this->http($this->app)
        );

        $this->http = new class ($this->app) extends Http {
            public function handle(Request $request): Response
            {
                return new Response('ok');
            }

            protected function dispatcherMiddleware(Request $request): array
            {
                return [TestKernelTerminate::class];
            }
        };
    }

    protected function tearDown(): void
    {
        $this->app->flush();

        parent::tearDown();
    }

    public function testCanTerminate(): void
    {
        $http = $this->app->make(Http::class);

        if (!$http instanceof Http) {
            throw new Exception('Expected an Http instance from the container.');
        }

        $response = $http->handle(
            $request = new Request('/test')
        );

        $this->app->registerTerminate(static function () {
            echo 'terminated.';
        });

        ob_start();
        $http->terminate($request, $response);
        $out = ob_get_clean();

        $this->assertEquals('/testokterminated.', $out);
    }
}
