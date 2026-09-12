<?php

declare(strict_types=1);

namespace Tests\Http;

use Exception;
use Omega\Router\Router;
use Omega\Testing\TestResponse;

use function Omega\Http\redirect;
use function Omega\Http\redirect_route;

covers(Router::class);
covers(TestResponse::class);
covers('Omega\Http\redirect');
covers('Omega\Http\redirect_route');

it('redirect to correct url', function (): void {
    Router::get('/test/(:any)', fn ($test) => $test)->name('test');
    $redirect = redirect_route('test', ['ok']);
    $response = new TestResponse($redirect);
    $response->assertStatusCode(302);
    $response->assertSee('Redirecting to /test/ok');

    Router::reset();
});

it('redirect to correct url with plan url', function (): void {
    Router::get('/test', fn ($test) => $test)->name('test');
    $redirect = redirect_route('test');
    $response = new TestResponse($redirect);
    $response->assertStatusCode(302);
    $response->assertSee('Redirecting to /test');

    Router::reset();
});

it('can redirect using given url', function (): void {
    $redirect = redirect('/test');
    $response = new TestResponse($redirect);
    $response->assertStatusCode(302);
    $response->assertSee('Redirecting to /test');
});