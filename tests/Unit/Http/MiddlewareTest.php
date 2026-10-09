<?php

declare(strict_types=1);

namespace Tests\Http;

use Closure;
use Exception;
use InvalidArgumentException;
use Omega\Application\Application;
use Omega\Application\ApplicationManifest;
use Omega\Container\Exceptions\CircularAliasException;
use Omega\Http\Http;
use Omega\Http\Request;
use Omega\Http\Response;
use PHPUnit\Framework\Attributes\CoversClass;
use Tests\Http\Support\ClassA;
use Tests\Http\Support\ClassB;
use Tests\Http\Support\ClassC;
use Tests\Http\Support\ClassD;
use Tests\TestCase;

use function is_string;
use function ob_get_clean;
use function ob_start;

#[CoversClass(CircularAliasException::class)]
#[CoversClass(Request::class)]
#[CoversClass(Response::class)]
#[CoversClass(Application::class)]
#[CoversClass(Http::class)]
#[CoversClass(ApplicationManifest::class)]
final class MiddlewareTest extends TestCase
{
    private Http $http;

    protected function setUp(): void
    {
        parent::setUp();

        $this->app = new Application(__DIR__ . '/fixtures/application-read/');

        $this->app->set(ApplicationManifest::class, fn () => new ApplicationManifest(
            basePath: is_string($path = $this->app->get('path.base')) ? $path : '',
            applicationCachePath: $this->app->getApplicationCachePath(),
            vendorPath: '/package/'
        ));

        $this->http = new Http($this->app);

        $this->app->set(Http::class, fn () => $this->http);
    }

    protected function tearDown(): void
    {
        $this->app->flush();

        parent::tearDown();
    }

    public function testCanHandleMiddlewareReversibleUsingClassString(): void
    {
        $middleware = [
            ClassA::class,
            ClassB::class,
            ClassC::class,
        ];

        $dispatcher = [
            'callable' => function ($param) {
                if (!is_string($param)) {
                    throw new Exception('Invalid middleware dispatcher parameter.');
                }

                echo $param;

                return new Response('');
            },
            'parameters' => [
                'param' => 'final response/',
            ],
            'middleware' => [],
        ];

        ob_start();
        $http = $this->http;
        $pipe = (fn (): Closure => $this->middlewarePipeline($middleware, $dispatcher))->call($http);
        $pipe(new Request('/'));
        $out = ob_get_clean();

        $this->assertEquals(
            'middleware.A.before/middleware.B.before/middleware.C.before/final response/'
                . 'middleware.C.after/middleware.A.after/',
            $out
        );
    }

    public function testThrowInvalidArgumentMethodNotFound(): void
    {
        $middleware = [ClassD::class];
        $dispatcher = [
            'callable' => function ($param) {
                if (!is_string($param)) {
                    throw new Exception('Invalid middleware dispatcher parameter.');
                }

                echo $param;

                return new Response($param);
            },
            'parameters' => [
                'param' => 'final response/',
            ],
            'middleware' => [],
        ];

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessageIsOrContains('Middleware must be a class with handle method');

        $http = $this->http;
        $pipe = (fn (): Closure => $this->middlewarePipeline($middleware, $dispatcher))->call($http);
        $pipe(new Request('/'));
    }
}
