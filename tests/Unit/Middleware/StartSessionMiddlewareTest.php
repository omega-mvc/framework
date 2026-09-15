<?php

declare(strict_types=1);

namespace Tests\Middleware;

use Omega\Http\Request;
use Omega\Http\Response;
use Omega\Middleware\StartSessionMiddleware;
use Omega\Session\SessionManager;
use Omega\Session\Storage\ArrayStorage;

use function expect;

covers(ArrayStorage::class);
covers(Request::class);
covers(Response::class);
covers(SessionManager::class);
covers(StartSessionMiddleware::class);

it('starts and saves the session for each request', function (): void {
    $storage    = new ArrayStorage();
    $session    = new SessionManager('array', $storage);
    $middleware = new StartSessionMiddleware($session);

    $session->setId('test-session-id');

    $handled = $middleware->handle(new Request('/'), function (Request $request) use ($session): Response {
        $session->put('name', 'omega');

        return new Response('content');
    });

    expect($session->getId())->toBe('test-session-id');
    expect($storage->read('test-session-id'))->toContain('"name":"omega"');
    expect($handled->getContent())->toBe('content');
});

it('returns the response produced by the next middleware', function (): void {
    $session    = new SessionManager('array', new ArrayStorage());
    $middleware = new StartSessionMiddleware($session);
    $response   = new Response('ok');

    $handled = $middleware->handle(new Request('/'), fn (Request $request) => $response);

    expect($handled)->toBe($response);
});