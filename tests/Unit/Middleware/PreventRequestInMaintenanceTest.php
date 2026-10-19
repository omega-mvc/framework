<?php

declare(strict_types=1);

namespace Tests\Middleware;

use Omega\Application\Application;
use Omega\Config\ConfigRepository;
use Omega\Container\Exceptions\BindingResolutionException;
use Omega\Container\Exceptions\CircularAliasException;
use Omega\Container\Exceptions\EntryNotFoundException;
use Omega\Http\Exceptions\HttpException;
use Omega\Http\Request;
use Omega\Http\Response;
use Omega\Middleware\MaintenanceMiddleware;
use PHPUnit\Framework\Attributes\CoversClass;
use Tests\TestCase;

#[CoversClass(Application::class)]
#[CoversClass(BindingResolutionException::class)]
#[CoversClass(CircularAliasException::class)]
#[CoversClass(ConfigRepository::class)]
#[CoversClass(EntryNotFoundException::class)]
#[CoversClass(HttpException::class)]
#[CoversClass(MaintenanceMiddleware::class)]
#[CoversClass(Request::class)]
#[CoversClass(Response::class)]
final class PreventRequestInMaintenanceTest extends TestCase
{
    public function testCanPreventRequestDuringMaintenance(): void
    {
        $app    = new Application(__DIR__);
        $config = [
            'APP_ENV'      => 'test',
            'APP_DEBUG'    => 'false',
            'STORAGE_PATH' => __DIR__ . '/fixtures/storage/',
        ];
        $app->loadConfig(new ConfigRepository($config));
        $middleware = new MaintenanceMiddleware($app);
        $response   = new Response('test');
        $handle     = $middleware->handle(new Request('/'), fn (Request $request) => $response);

        $this->assertSame($response, $handle);
    }

    public function testCanRedirectRequestDuringMaintenance(): void
    {
        $app = new Application(__DIR__);
        $app->set('path.storage', __DIR__ . '/fixtures/application-read/storage/');

        $middleware = new MaintenanceMiddleware($app);

        $response = new Response('test');
        $handle   = $middleware->handle(new Request('/'), fn (Request $request) => $response);

        $this->assertSame('/test', $handle->headers->get('Location'));
    }

    public function testCanRenderAndRetryRequestDuringMaintenance(): void
    {
        $app = new Application(__DIR__);
        $app->set('path.storage', __DIR__ . '/fixtures/application-read/storage2/');

        $middleware = new MaintenanceMiddleware($app);
        $response   = new Response('test');
        $handle     = $middleware->handle(new Request('/'), fn (Request $request) => $response);

        $this->assertSame('<h1>Test</h1>', $handle->getContent());
        $this->assertSame(15, $handle->headers->get('Retry-After'));
        $this->assertSame(503, $handle->getStatusCode());
    }

    public function testCanThrowRequestDuringMaintenance(): void
    {
        $app = new Application(__DIR__);
        $app->set('path.storage', __DIR__ . '/fixtures/application-read/storage3/');

        $middleware = new MaintenanceMiddleware($app);
        $response   = new Response('test');

        $this->expectException(HttpException::class);

        $middleware->handle(new Request('/'), fn (Request $request) => $response);
    }
}
