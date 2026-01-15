<?php

/**
 * Part of Omega - Router Package.
 *
 * @link      https://omega-mvc.github.io
 * @author    Adriano Giovannini <agisoftt@gmail.com>
 * @copyright Copyright (c) 2025 - 2026 Adriano Giovannini (https://omega-mvc.github.io)
 * @license   https://www.gnu.org/licenses/gpl-3.0-standalone.html     GPL V3.0+
 * @version   2.0.0
 */

declare(strict_types=1);

namespace Omega\Router;

use Closure;

/**
 * Class RouteGroup
 *
 * Handles grouping of routes by executing setup and cleanup callbacks
 * around a given route registration closure. Useful for applying middleware,
 * prefixes, or other route group configurations.
 *
 * @category  Omega
 * @package   Router
 * @link      https://omega-mvc.github.io
 * @author    Adriano Giovannini <agisoftt@gmail.com>
 * @copyright Copyright (c) 2025 - 2026 Adriano Giovannini (https://omega-mvc.github.io)
 * @license   https://www.gnu.org/licenses/gpl-3.0-standalone.html     GPL V3.0+
 * @version   2.0.0
 */
readonly class RouteGroup
{
    /**
     * RouteGroup constructor.
     *
     * @param Closure $setup   Callback executed before the group.
     * @param Closure $cleanup Callback executed after the group.
     * @return void
     */
    public function __construct(private Closure $setup, private Closure $cleanup)
    {
    }

    /**
     * Execute a route group with setup and cleanup hooks.
     *
     * @template T
     * @param callable(): T $callback Callback that defines routes inside the group.
     * @return T Returns the result of the callback execution.
     */
    public function group(callable $callback)
    {
        // Execute setup callback
        ($this->setup)();

        // Execute the user-provided callback
        $result = ($callback)();

        // Execute cleanup callback
        ($this->cleanup)();

        return $result;
    }
}
