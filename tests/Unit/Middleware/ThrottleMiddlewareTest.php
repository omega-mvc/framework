<?php

declare(strict_types=1);

namespace Tests\Middleware;

use Omega\Cache\Storage\MemoryStorage;
use Omega\Http\Request;
use Omega\Http\Response;
use Omega\Middleware\ThrottleMiddleware;
use Omega\RateLimiter\Policy\FixedWindow;
use Omega\RateLimiter\RateLimiter;

use function expect;
use function getenv;

covers(FixedWindow::class);
covers(MemoryStorage::class);
covers(RateLimiter::class);
covers(Request::class);
covers(Response::class);
covers(ThrottleMiddleware::class);

beforeEach(function (): void {
    $testMode = getenv('OMEGA_TEST_MODE') ?: '';

    $this->clock = ($testMode === 'light' || getenv('CI') || getenv('GITHUB_ACTIONS')) ? 60 : 1;
});

it('can throttle request', function (): void {
    $limiter     = new RateLimiter(new FixedWindow(new MemoryStorage(['ttl' => 3_600]), 60, $this->clock));
    $middleware  = new ThrottleMiddleware($limiter);
    $request     = new Request('/');

    for ($i = 0; $i < 60; $i++) {
        $middleware->handle($request, fn (Request $request) => new Response(''));
    }

    $response = $middleware->handle($request, fn (Request $request) => new Response(''));

    expect($response->getStatusCode())->toBe(429);
    expect($response->getContent())->toBe('Too Many Requests');
    expect($response->headers->get('X-RateLimit-Limit'))->toBe('60');
    expect($response->headers->get('X-RateLimit-Remaining'))->toBe('0');
});

it('can pass request', function (): void {
    $limiter    = new RateLimiter(new FixedWindow(new MemoryStorage(['ttl'  => 3_600]), 60, $this->clock));
    $middleware = new ThrottleMiddleware($limiter);
    $request    = new Request('/');

    for ($i = 0; $i < 58; $i++) {
        $middleware->handle($request, fn (Request $request) => new Response(''));
    }

    $response = $middleware->handle($request, fn (Request $request) => new Response(''));

    expect($response->getStatusCode())->toBe(200);
    expect($response->getContent())->toBe('');
    expect($response->headers->get('X-RateLimit-Limit'))->toBe('60');
    expect($response->headers->get('X-RateLimit-Remaining'))->toBe('1');
});
