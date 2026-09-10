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
 * Event dispatched when a log entry is written.
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
class LogEvent extends Event
{
    /**
     * Create a log.written event.
     *
     * @param string $level   The log level.
     * @param string $message The log message.
     * @param array  $context The log context.
     * @return static
     */
    public static function written(string $level, string $message, array $context = []): static
    {
        $event = new static('log.written');
        $event->setArgument('level', $level);
        $event->setArgument('message', $message);
        $event->setArgument('context', $context);

        return $event;
    }
}
