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
 * Class SomeClass
 *
 * A simple controller-like class used in tests for grouped and controller-based routes.
 *
 * This class provides methods that are mapped to routes in `GroupRouteTest` to
 * verify that route grouping, prefixing, and controller dispatching work correctly.
 * The methods are intentionally simple and return inverted string values to
 * facilitate assertions in the test cases.
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
class SomeClass
{
    /**
     * Simulates a route action for '/foo'.
     *
     * Echoes 'bar' to be captured by the test dispatcher and asserted.
     *
     * @return void
     */
    public function foo(): void
    {
        echo 'bar';
    }

    /**
     * Simulates a route action for '/bar'.
     *
     * Echoes 'foo' to be captured by the test dispatcher and asserted.
     *
     * @return void
     */
    public function bar(): void
    {
        echo 'foo';
    }
}
