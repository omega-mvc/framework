<?php

/**
 * Part of Omega - Tests\Router Package.
 *
 * @link      https://omega-mvc.github.io
 * @author    Adriano Giovannini <agisoftt@gmail.com>
 * @copyright Copyright (c) 2025 - 2026 Adriano Giovannini (https://omega-mvc.github.io)
 * @license   https://www.gnu.org/licenses/gpl-3.0-standalone.html     GPL V3.0+
 * @version   2.0.0
 */

declare(strict_types=1);

namespace Tests\Router\Support;

use PHPUnit\Framework\Attributes\CoversNothing;

/**
 * Class TestMiddleware
 *
 * A simple middleware class used in BasicRouteTest to verify that route middleware
 * is executed correctly for both single routes and route groups.
 *
 * This middleware updates a global indicator in the $_SERVER superglobal and tracks
 * the number of times it has been invoked using the static property $last.
 *
 * It is intended only for testing purposes, to assert that middleware registration
 * and execution in the Omega Router system behave as expected.
 *
 * @category   Tests
 * @package    Router
 * @subpackage Support
 * @link       https://omega-mvc.github.io
 * @author     Adriano Giovannini <agisoftt@gmail.com>
 * @copyright  Copyright (c) 2025 - 2026 Adriano Giovannini (https://omega-mvc.github.io)
 * @license    https://www.gnu.org/licenses/gpl-3.0-standalone.html     GPL V3.0+
 * @version    2.0.0
 */
#[CoversNothing]
class TestMiddleware
{
    /**
     * Tracks the number of times this middleware has been executed.
     *
     * This static counter is reset by tests before running routes that use this
     * middleware, and incremented every time handle() is called.
     *
     * @var int
     */
    public static int $last = 0;

    /**
     * Executes the middleware logic.
     *
     * Sets $_SERVER['middleware'] to 'oke' and increments the static $last counter.
     * This allows tests to verify that middleware runs exactly once or multiple times
     * as expected when applied globally or to individual routes.
     *
     * @return void
     */
    public function handle(): void
    {
        $_SERVER['middleware'] = 'oke';
        self::$last++;
    }
}
