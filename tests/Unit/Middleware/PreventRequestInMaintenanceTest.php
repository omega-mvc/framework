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
use Tests\FixturesPathTrait;

use function expect;

covers(Application::class);
covers(BindingResolutionException::class);
covers(CircularAliasException::class);
covers(ConfigRepository::class);
covers(EntryNotFoundException::class);
covers(HttpException::class);
covers(MaintenanceMiddleware::class);
covers(Request::class);
covers(Response::class);

uses(FixturesPathTrait::class);

it('can prevent request during maintenance', function (): void {
    $app    = new Application($this->setFixtureBasePath());
    $config = [
        'APP_ENV'      => 'test',
        'APP_DEBUG'    => 'false',
        'STORAGE_PATH' => $this->setFixturePath('/fixtures/storage/'),
    ];
    $app->loadConfig(new ConfigRepository($config));
    $middleware = new MaintenanceMiddleware($app);
    $response   = new Response('test');
    $handle     = $middleware->handle(new Request('/'), fn (Request $request) => $response);

    expect($handle)->toBe($response);
});

it('can redirect request during maintenance', function (): void {
    $app = new Application($this->setFixtureBasePath());
    $app->set('path.storage', $this->setFixturePath('/fixtures/application-read/storage/'));

    $middleware = new MaintenanceMiddleware($app);

    $response = new Response('test');
    $handle   = $middleware->handle(new Request('/'), fn (Request $request) => $response);

    expect($handle->headers->get('Location'))->toBe('/test');
});

it('can render and retry request during maintenance', function (): void {
    $app = new Application($this->setFixtureBasePath());
    $app->set('path.storage', $this->setFixturePath('/fixtures/application-read/storage2/'));

    $middleware = new MaintenanceMiddleware($app);
    $response   = new Response('test');
    $handle     = $middleware->handle(new Request('/'), fn (Request $request) => $response);

    expect($handle->getContent())->toBe('<h1>Test</h1>');
    expect($handle->headers->get('Retry-After'))->toBe(15);
    expect($handle->getStatusCode())->toBe(503);
});

it('can throw request during maintenance', function (): void {
    $app = new Application($this->setFixtureBasePath());
    $app->set('path.storage', $this->setFixturePath('/fixtures/application-read/storage3/'));

    $middleware = new MaintenanceMiddleware($app);
    $response   = new Response('test');

    $middleware->handle(new Request('/'), fn (Request $request) => $response);
})->throws(HttpException::class);