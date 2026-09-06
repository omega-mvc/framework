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

namespace Tests\Http\Support;

use Closure;
use Omega\Http\Request;
use Omega\Http\Response;
use PHPUnit\Framework\Attributes\CoversNothing;

/**
 * Middleware ClassD
 *
 * Demonstrates a middleware implemented as an invokable class. This class
 * intentionally lacks a handle() method to test invalid middleware handling.
 *
 * @category   Tests
 * @package    Http
 * @subpackage Support
 * @link       https://omega-mvc.github.io
 * @author     Adriano Giovannini <agisoftt@gmail.com>
 * @copyright  Copyright (c) 2025 - 2026 Adriano Giovannini (https://omega-mvc.github.io)
 * @license    https://www.gnu.org/licenses/gpl-3.0-standalone.html     GPL V3.0+
 * @version    2.0.0
 */
#[CoversNothing]
final class ClassD
{
    /**
     * Invoke the middleware with request and next callback.
     *
     * @param Request $request The current HTTP request instance.
     * @param Closure $next    The next middleware in the pipeline.
     * @return Response Returns the response after processing the middleware chain.
     */
    public function __invoke(Request $request, Closure $next): Response
    {
        echo 'middleware.D.before/';
        $response = $next($request);
        echo 'middleware.D.after/';

        return $response;
    }
}
