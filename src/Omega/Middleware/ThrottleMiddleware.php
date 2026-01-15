<?php

/**
 * Part of Omega - Middleware Package.
 *
 * @link      https://omega-mvc.github.io
 * @author    Adriano Giovannini <agisoftt@gmail.com>
 * @copyright Copyright (c) 2025 - 2026 Adriano Giovannini (https://omega-mvc.github.io)
 * @license   https://www.gnu.org/licenses/gpl-3.0-standalone.html     GPL V3.0+
 * @version   2.0.0
 */

/** @noinspection PhpUnusedParameterInspection */

declare(strict_types=1);

namespace Omega\Middleware;

use Closure;
use Omega\Http\Request;
use Omega\Http\Response;
use Omega\RateLimiter\RateLimiterInterface;

/**
 * Middleware that limits the number of requests a client can make within a given time frame.
 *
 * This middleware uses a rate limiter to track requests per key (typically IP address) and
 * returns a 429 "Too Many Requests" response when the limit is exceeded.
 *
 * @category  Omega
 * @package   Middleware
 * @link      https://omega-mvc.github.io
 * @author    Adriano Giovannini <agisoftt@gmail.com>
 * @copyright Copyright (c) 2025 - 2026 Adriano Giovannini (https://omega-mvc.github.io)
 * @license   https://www.gnu.org/licenses/gpl-3.0-standalone.html     GPL V3.0+
 * @version   2.0.0
 */
class ThrottleMiddleware
{
    /**
     * Create a new ThrottleMiddleware instance.
     *
     * @param RateLimiterInterface $limiter The rate limiter instance used to track and enforce limits.
     */
    public function __construct(protected RateLimiterInterface $limiter)
    {
    }

    /**
     * Handle an incoming request.
     *
     * This method checks if the client has exceeded the allowed request limit. If so, it
     * returns a 429 response. Otherwise, it consumes a rate limit slot and forwards
     * the request to the next middleware or controller.
     *
     * @param Request $request The incoming HTTP request.
     * @param Closure $next The next middleware or request handler.
     * @return Response The HTTP response, either rate-limited or forwarded.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $key = $this->resolveRequestKey($request);
        if ($this->limiter->isBlocked($key, $maxAttempts = 60, $decayMinutes = 1)) {
            return $this->rateLimitedResponse(
                key: $key,
                maxAttempts: $maxAttempts,
                remainingAfter: $this->calculateRemainingAttempts(
                    key: $key,
                    maxAttempts: $maxAttempts,
                    retryAfter: $this->limiter->getRetryAfter($key)
                )
            );
        }

        $this->limiter->consume($key, $decayMinutes);

        /** @var Response $response */
        $response = $next($request);

        $response->headers->add(
            $this->rateLimitedHeader(
                maxAttempts: $maxAttempts,
                remainingAfter: $this->calculateRemainingAttempts(
                    key: $key,
                    maxAttempts: $maxAttempts,
                    retryAfter: null
                )
            )
        );

        return $response;
    }

    /**
     * Resolve a unique key for the request used by the rate limiter.
     *
     * Typically based on the client IP address, hashed for privacy.
     *
     * @param Request $request The incoming HTTP request.
     * @return string The unique hashed key for rate limiting.
     */
    protected function resolveRequestKey(Request $request): string
    {
        $key = $request->getRemoteAddress();
        return sha1($key);
    }

    /**
     * Generate a rate-limited response with HTTP status 429.
     *
     * @param string $key The key representing the client.
     * @param int $maxAttempts Maximum allowed attempts within the time window.
     * @param int $remainingAfter Remaining attempts before hitting the limit.
     * @param int|null $retryAfter Optional seconds until the rate limit resets.
     * @return Response The HTTP 429 response with rate-limit headers.
     */
    protected function rateLimitedResponse(
        string $key,
        int $maxAttempts,
        int $remainingAfter,
        ?int $retryAfter = null
    ): Response {
        return new Response(
            'Too Many Requests',
            429,
            $this->rateLimitedHeader(
                $maxAttempts,
                $remainingAfter,
                $remainingAfter
            )
        );
    }

    /**
     * Build HTTP headers indicating rate limit status.
     *
     * @param int $maxAttempts Maximum allowed attempts.
     * @param int $remainingAfter Remaining attempts before hitting the limit.
     * @param int|null $retryAfter Optional seconds until the rate limit resets.
     * @return array<string, string> An associative array of HTTP headers for rate limiting.
     */
    protected function rateLimitedHeader(int $maxAttempts, int $remainingAfter, ?int $retryAfter = null): array
    {
        $header = [
            'X-RateLimit-Limit'     => (string) $maxAttempts,
            'X-RateLimit-Remaining' => (string) $remainingAfter,
        ];

        if ($retryAfter !== null) {
            $header['Retry-After'] = (string) $retryAfter;
        }

        return $header;
    }

    /**
     * Calculate remaining allowed attempts for the client.
     *
     * @param string $key The key representing the client.
     * @param int $maxAttempts Maximum allowed attempts within the time window.
     * @param int|null $retryAfter Optional seconds until the rate limit resets.
     * @return int Number of remaining attempts before the client is blocked.
     */
    public function calculateRemainingAttempts(string $key, int $maxAttempts, ?int $retryAfter): int
    {
        if (null !== $retryAfter) {
            return 0;
        }

        return $this->limiter->remaining($key, $maxAttempts);
    }
}
