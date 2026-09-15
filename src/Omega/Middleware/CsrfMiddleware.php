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

declare(strict_types=1);

namespace Omega\Middleware;

use Closure;
use InvalidArgumentException;
use Omega\Csrf\CsrfInterface;
use Omega\Csrf\Exceptions\InvalidCsrfTokenException;
use Omega\Http\Request;
use Omega\Http\Response;

use function is_string;

class CsrfMiddleware
{
    public function __construct(private CsrfInterface $csrf)
    {
    }

    public function handle(Request $request, Closure $next): Response
    {
        if (!$request->isMethod('GET') && !$request->isMethod('HEAD') && !$request->isMethod('OPTIONS')) {
            $token = $request->getPost($this->csrf->getTokenField());

            if (!is_string($token) || !$this->csrf->validateToken($token)) {
                throw new InvalidCsrfTokenException('Invalid CSRF token.');
            }
        }

        $response = $next($request);

        if (!$response instanceof Response) {
            throw new InvalidArgumentException('Middleware must return a Response instance.');
        }

        return $response;
    }
}
