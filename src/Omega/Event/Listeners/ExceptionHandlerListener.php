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

namespace Omega\Event\Listeners;

use Omega\Event\EventInterface;
use Omega\Event\SubscriberInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;

/**
 * Listener that logs exceptions via the event system.
 *
 * This listener can be registered to handle exception.logged events,
 * providing a decoupled way to add additional logging behavior.
 *
 * @category  Omega
 * @package   Event
 * @subpackage Listeners
 * @link      https://omega-mvc.github.io
 * @author    Adriano Giovannini <agisoftt@gmail.com>
 * @copyright Copyright (c) 2025 - 2026 Adriano Giovannini (https://omega-mvc.github.io)
 * @license   https://www.gnu.org/licenses/gpl-3.0-standalone.html     GPL V3.0+
 * @version   2.0.0
 */
class ExceptionHandlerListener implements SubscriberInterface
{
    /**
     * @var LoggerInterface|null The logger instance.
     */
    private ?LoggerInterface $logger = null;

    /**
     * Set the logger instance.
     *
     * @param LoggerInterface $logger The logger to use.
     * @return void
     */
    public function setLogger(LoggerInterface $logger): void
    {
        $this->logger = $logger;
    }

    /**
     * Returns an array of event names this subscriber wants to listen to.
     *
     * @return array<string, array{0: string, 1?: int}> Event names to listen for.
     */
    public static function getSubscribedEvents(): array
    {
        return [
            'exception.logged' => ['onExceptionLogged', 10],
        ];
    }

    /**
     * Handle the exception.logged event.
     *
     * @param EventInterface $event The exception event.
     * @return void
     */
    public function onExceptionLogged(EventInterface $event): void
    {
        if ($this->logger === null) {
            return;
        }

        $exception = $event->getArgument('exception');
        $level     = $event->getArgument('level', LogLevel::ERROR);

        if ($exception instanceof \Throwable) {
            $this->logger->log(
                $level,
                'Exception caught: ' . $exception->getMessage(),
                [
                    'exception' => $exception::class,
                    'file'      => $exception->getFile(),
                    'line'      => $exception->getLine(),
                ]
            );
        }
    }
}
