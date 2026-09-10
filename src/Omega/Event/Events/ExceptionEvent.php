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
use Throwable;

/**
 * Event dispatched when an exception is logged.
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
class ExceptionEvent extends Event
{
    /**
     * Create a new ExceptionEvent.
     *
     * @param Throwable $exception The exception that was logged.
     * @param string    $level     The log level used.
     * @return static
     */
    public static function create(Throwable $exception, string $level): static
    {
        $event = new static('exception.logged');
        $event->setArgument('exception', $exception);
        $event->setArgument('level', $level);
        $event->setArgument('message', $exception->getMessage());
        $event->setArgument('class', $exception::class);

        return $event;
    }
}
