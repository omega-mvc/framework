<?php

/**
 * Part of Omega - Event Package.
 *
 * @link      https://omega-mvc.github.io
 * @author    Adriano Giovannini <agisoftt@gmail.com>
 * @copyright Copyright (c) 2025 - 2026 Adriano Giovannini (https://omega-mvc.github.io)
 * @license   https://www.gnu.org/licenses/gpl-3.0-standalone.html     GPL V3.0+
 * @version   2.0.0
 */

declare(strict_types=1);

namespace Omega\Event\Events;

use Omega\Event\Event;

/**
 * Event dispatched during route matching.
 *
 * @category  Omega
 * @package   Event
 * @subpackage Events
 * @link      https://omega-mvc.github.io
 * @author    Adriano Giovannini <agisoftt@gmail.com>
 * @copyright Copyright (c) 2025 - 2026 Adriano Giovannini (https://omega-mvc.github.io)
 * @license   https://www.gnu.org/licenses/gpl-3.0-standalone.html     GPL V3.0+
 * @version   2.0.0
 */
class RouteEvent extends Event
{
    /**
     * Create a route.before event.
     *
     * @param string $uri    The request URI.
     * @param string $method The HTTP method.
     * @return static
     */
    public static function before(string $uri, string $method): static
    {
        $event = new static('route.before');
        $event->setArgument('uri', $uri);
        $event->setArgument('method', $method);

        return $event;
    }

    /**
     * Create a route.after event.
     *
     * @param string $uri        The request URI.
     * @param string $method     The HTTP method.
     * @param mixed  $callable   The matched callable.
     * @param array  $parameters The route parameters.
     * @return static
     */
    public static function after(string $uri, string $method, mixed $callable, array $parameters): static
    {
        $event = new static('route.after');
        $event->setArgument('uri', $uri);
        $event->setArgument('method', $method);
        $event->setArgument('callable', $callable);
        $event->setArgument('parameters', $parameters);

        return $event;
    }
}
