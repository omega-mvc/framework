<?php

/**
 * Part of Omega - Tests\Http Package.
 *
 * @link      https://omega-mvc.github.io
 * @author    Adriano Giovannini <agisoftt@gmail.com>
 * @copyright Copyright (c) 2025 - 2026 Adriano Giovannini (https://omega-mvc.github.io)
 * @license   https://www.gnu.org/licenses/gpl-3.0-standalone.html     GPL V3.0+
 * @version   2.0.0
 */

declare(strict_types=1);

namespace Tests\Middleware;

use Omega\Cache\Storage\Memory;
use Omega\Container\Exceptions\BindingResolutionException;
use Omega\Container\Exceptions\CircularAliasException;
use Omega\Container\Exceptions\EntryNotFoundException;
use Omega\Http\Request;
use Omega\Http\Response;
use Omega\Middleware\ThrottleMiddleware;
use Omega\RateLimiter\Policy\FixedWindow;
use Omega\RateLimiter\RateLimiter;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerExceptionInterface;
use ReflectionException;

/**
 * Test suite for ThrottleMiddleware.
 *
 * Verifies that the middleware correctly enforces rate limiting
 * using a fixed window strategy, returns HTTP 429 when the limit
 * is exceeded, and properly sets rate limit headers for both
 * throttled and allowed requests.
 *
 * @category  Tests
 * @package   Middleware
 * @link      https://omega-mvc.github.io
 * @author    Adriano Giovannini <agisoftt@gmail.com>
 * @copyright Copyright (c) 2025 - 2026 Adriano Giovannini (https://omega-mvc.github.io)
 * @license   https://www.gnu.org/licenses/gpl-3.0-standalone.html     GPL V3.0+
 * @version   2.0.0
 */
#[CoversClass(BindingResolutionException::class)]
#[CoversClass(CircularAliasException::class)]
#[CoversClass(EntryNotFoundException::class)]
#[CoversClass(Memory::class)]
#[CoversClass(Request::class)]
#[CoversClass(Response::class)]
#[CoversClass(ThrottleMiddleware::class)]
#[CoversClass(FixedWindow::class)]
#[CoversClass(RateLimiter::class)]
final class ThrottleMiddlewareTest extends TestCase
{
    private RateLimiter $limiter;
    private int $ttl;

    protected function setUp(): void
    {
        parent::setUp();

        // Use longer TTL for Android/Termux environments to avoid premature expiration
        $this->ttl = omega_local_override() ? 86_400 : 3_600; // 1 day vs 1 hour

        // Always create a fresh memory instance for isolation
        $memory = new Memory(['ttl' => $this->ttl]);
        $memory->clear();
        $this->limiter = new RateLimiter(new FixedWindow($memory, 60, 1));
    }

    protected function tearDown(): void
    {
        // Ensure memory is cleared to avoid cross-test contamination
        //$this->limiter->reset();
        parent::tearDown();
    }

    /**
     * Test it can throttle request.
     *
     * @return void
     * @throws BindingResolutionException Thrown when resolving a binding fails.
     * @throws CircularAliasException Thrown when alias resolution loops recursively.
     * @throws ContainerExceptionInterface Thrown on general container errors, e.g., service not retrievable.
     * @throws EntryNotFoundException Thrown when no entry exists for the identifier.
     * @throws ReflectionException Thrown when the requested class or interface cannot be reflected.
     */
    public function testItCanThrottleRequest(): void
    {
        $middleware = new ThrottleMiddleware($this->limiter);
        $request = new Request('/');

        // Simulate 60 requests to trigger throttling
        for ($i = 0; $i < 60; $i++) {
            $middleware->handle($request, fn (Request $request) => new Response(''));
        }

        $response = $middleware->handle($request, fn (Request $request) => new Response(''));

        $this->assertEquals(429, $response->getStatusCode());
        $this->assertEquals('Too Many Requests', $response->getContent());
        $this->assertEquals('60', $response->headers->get('X-RateLimit-Limit'));
        $this->assertEquals('0', $response->headers->get('X-RateLimit-Remaining'));
    }

    /**
     * Test it can pass request.
     *
     * @return void
     * @throws BindingResolutionException Thrown when resolving a binding fails.
     * @throws CircularAliasException Thrown when alias resolution loops recursively.
     * @throws ContainerExceptionInterface Thrown on general container errors, e.g., service not retrievable.
     * @throws EntryNotFoundException Thrown when no entry exists for the identifier.
     * @throws ReflectionException Thrown when the requested class or interface cannot be reflected.
     */
    public function testItCanPassRequest(): void
    {
        $middleware = new ThrottleMiddleware($this->limiter);
        $request = new Request('/');

        // Simulate 59 requests, so one remaining
        for ($i = 0; $i < 58; $i++) {
            $middleware->handle($request, fn (Request $request) => new Response(''));
        }

        $response = $middleware->handle($request, fn (Request $request) => new Response(''));

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('', $response->getContent());
        $this->assertEquals('60', $response->headers->get('X-RateLimit-Limit'));
        $this->assertEquals('1', $response->headers->get('X-RateLimit-Remaining'));
    }
}
