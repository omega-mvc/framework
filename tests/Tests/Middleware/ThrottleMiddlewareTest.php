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
use Omega\Http\Request;
use Omega\Http\Response;
use Omega\Middleware\ThrottleMiddleware;
use Omega\RateLimiter\Policy\FixedWindow;
use Omega\RateLimiter\RateLimiter;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

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
#[CoversClass(Memory::class)]
#[CoversClass(Request::class)]
#[CoversClass(Response::class)]
#[CoversClass(ThrottleMiddleware::class)]
#[CoversClass(FixedWindow::class)]
#[CoversClass(RateLimiter::class)]
final class ThrottleMiddlewareTest extends TestCase
{
	/** @var int Number of simulated requests for the test, adapted for Android environments. */
	private int $clock;

    /**
     * Sets up the environment before each test method.
     *
     * This method is called automatically by PHPUnit before each test runs.
     * It is responsible for initializing the application instance, setting up
     * dependencies, and preparing any state required by the test.
     *
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();

        $testMode = getenv('OMEGA_TEST_MODE') ?: '';
        if ($testMode === 'light' || getenv('CI') || getenv('GITHUB_ACTIONS')) {
            $this->clock = 60;
        } else {
            $this->clock = 1;
        }
    }

    /**
     * Test it can throttle request.
     *
     * @return void
     */
    public function testItCanThrottleRequest(): void
    {
        $limiter    = new RateLimiter(new FixedWindow(new Memory(['ttl' => 3_600]), 60, $this->clock));
        $middleware = new ThrottleMiddleware($limiter);
        $request    = new Request('/');

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
     */
    public function testItCanPassRequest(): void
    {
        $limiter    = new RateLimiter(new FixedWindow(new Memory(['ttl'  => 3_600]), 60, $this->clock));
        $middleware = new ThrottleMiddleware($limiter);
        $request    = new Request('/');

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
