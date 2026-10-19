<?php

declare(strict_types=1);

namespace Tests\Middleware;

use Omega\Cache\Storage\MemoryStorage;
use Omega\Http\Request;
use Omega\Http\Response;
use Omega\Middleware\ThrottleMiddleware;
use Omega\RateLimiter\Policy\FixedWindow;
use Omega\RateLimiter\RateLimiter;
use PHPUnit\Framework\Attributes\CoversClass;
use Tests\TestCase;

use function getenv;

#[CoversClass(FixedWindow::class)]
#[CoversClass(MemoryStorage::class)]
#[CoversClass(RateLimiter::class)]
#[CoversClass(Request::class)]
#[CoversClass(Response::class)]
#[CoversClass(ThrottleMiddleware::class)]
final class ThrottleMiddlewareTest extends TestCase
{
    private int $clock;

    protected function setUp(): void
    {
        parent::setUp();

        $testMode = getenv('OMEGA_TEST_MODE') ?: '';

        $this->clock = ($testMode === 'light' || getenv('CI') || getenv('GITHUB_ACTIONS')) ? 60 : 1;
    }

    public function testCanThrottleRequest(): void
    {
        $limiter    = new RateLimiter(new FixedWindow(new MemoryStorage(['ttl' => 3_600]), 60, $this->clock));
        $middleware = new ThrottleMiddleware($limiter);
        $request    = new Request('/');

        for ($i = 0; $i < 60; $i++) {
            $middleware->handle($request, fn (Request $request) => new Response(''));
        }

        $response = $middleware->handle($request, fn (Request $request) => new Response(''));

        $this->assertSame(429, $response->getStatusCode());
        $this->assertSame('Too Many Requests', $response->getContent());
        $this->assertSame('60', $response->headers->get('X-RateLimit-Limit'));
        $this->assertSame('0', $response->headers->get('X-RateLimit-Remaining'));
    }

    public function testCanPassRequest(): void
    {
        $limiter    = new RateLimiter(new FixedWindow(new MemoryStorage(['ttl' => 3_600]), 60, $this->clock));
        $middleware = new ThrottleMiddleware($limiter);
        $request    = new Request('/');

        for ($i = 0; $i < 58; $i++) {
            $middleware->handle($request, fn (Request $request) => new Response(''));
        }

        $response = $middleware->handle($request, fn (Request $request) => new Response(''));

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('', $response->getContent());
        $this->assertSame('60', $response->headers->get('X-RateLimit-Limit'));
        $this->assertSame('1', $response->headers->get('X-RateLimit-Remaining'));
    }
}
