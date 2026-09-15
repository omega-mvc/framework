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
use Omega\Http\Request;
use Omega\Http\Response;
use Omega\Session\SessionManager;

/**
 * Middleware that starts and saves the session for each request.
 *
 * Resolves the session manager from the container, starts the session
 * before the request is handled, and persists it afterwards.
 *
 * @category   Omega
 * @package    Middleware
 * @link       https://omega-mvc.github.io
 * @author     Adriano Giovannini <agisoftt@gmail.com>
 * @copyright  Copyright (c) 2025 - 2026 Adriano Giovannini (https://omega-mvc.github.io)
 * @license    https://www.gnu.org/licenses/gpl-3.0-standalone.html     GPL V3.0+
 * @version    2.0.0
 */
class StartSessionMiddleware
{
    public function __construct(private SessionManager $session)
    {
    }

    public function handle(Request $request, Closure $next): Response
    {
        $this->session->start();

        /** @var Response $response */
        $response = $next($request);

        $this->session->save();

        return $response;
    }
}
