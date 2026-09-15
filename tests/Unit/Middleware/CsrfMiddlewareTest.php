<?php

declare(strict_types=1);

namespace Tests\Middleware;

use InvalidArgumentException;
use Omega\Csrf\Csrf;
use Omega\Csrf\Exceptions\InvalidCsrfTokenException;
use Omega\Http\Request;
use Omega\Http\Response;
use Omega\Middleware\CsrfMiddleware;
use Omega\Session\SessionManager;
use Omega\Session\Storage\ArrayStorage;

use function expect;

covers(ArrayStorage::class);
covers(Csrf::class);
covers(CsrfMiddleware::class);
covers(InvalidCsrfTokenException::class);
covers(Request::class);
covers(Response::class);
covers(SessionManager::class);

beforeEach(function (): void {
    $session          = new SessionManager('array', new ArrayStorage());
    $this->csrf       = new Csrf($session);
    $this->middleware = new CsrfMiddleware($this->csrf);
});

it('passes safe request methods without a token', function (string $method): void {
    $response = new Response('ok');
    $handled  = $this->middleware->handle(
        new Request('/', [], [], [], [], [], [], $method),
        fn (Request $request) => $response
    );

    expect($handled)->toBe($response);
})->with(['GET', 'HEAD', 'OPTIONS']);

it('throws an exception when a POST request lacks a token', function (): void {
    $this->middleware->handle(
        new Request('/', [], [], [], [], [], [], 'POST'),
        fn (Request $request) => new Response('ok')
    );
})->throws(InvalidCsrfTokenException::class);

it('throws an exception when a POST request sends an invalid token', function (): void {
    $this->middleware->handle(
        new Request('/', [], ['_csrf_token' => 'invalid'], [], [], [], [], 'POST'),
        fn (Request $request) => new Response('ok')
    );
})->throws(InvalidCsrfTokenException::class);

it('passes a POST request carrying a valid token', function (): void {
    $token = $this->csrf->generateToken();

    $response = new Response('ok');
    $handled  = $this->middleware->handle(
        new Request('/', [], ['_csrf_token' => $token], [], [], [], [], 'POST'),
        fn (Request $request) => $response
    );

    expect($handled)->toBe($response);
});

it('throws an exception when the next middleware does not return a Response', function (): void {
    $token = $this->csrf->generateToken();

    $this->middleware->handle(
        new Request('/', [], ['_csrf_token' => $token], [], [], [], [], 'POST'),
        fn (Request $request) => null
    );
})->throws(InvalidArgumentException::class);