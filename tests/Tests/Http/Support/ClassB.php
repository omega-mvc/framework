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
 * Middleware ClassB
 *
 * Demonstrates a middleware that executes code before passing the request to
 * the next middleware, but does not implement reversible logic after execution.
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
final class ClassB
{
    /**
     * Handle the request and execute middleware logic.
     *
     * @param Request $request The current HTTP request instance.
     * @param Closure $next    The next middleware in the pipeline.
     * @return Response Returns the response after passing to the next middleware.
     */
    public function handle(Request $request, Closure $next): Response
    {
        echo 'middleware.B.before/';

        // skip reversible middleware
        return $next($request);
    }
}
