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

use Omega\Database\Model\Model;
use Omega\Event\EventInterface;
use Omega\Event\SubscriberInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;

/**
 * Listener that creates an audit trail for model operations.
 *
 * This listener can be registered to handle model events,
 * logging all create, update, and delete operations.
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
class AuditTrailListener implements SubscriberInterface
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
            'model.created' => ['onModelCreated', 0],
            'model.saved'   => ['onModelSaved', 0],
            'model.deleted' => ['onModelDeleted', 0],
        ];
    }

    /**
     * Handle the model.created event.
     *
     * @param EventInterface $event The model event.
     * @return void
     */
    public function onModelCreated(EventInterface $event): void
    {
        $this->logOperation('created', $event);
    }

    /**
     * Handle the model.saved event.
     *
     * @param EventInterface $event The model event.
     * @return void
     */
    public function onModelSaved(EventInterface $event): void
    {
        $created = $event->getArgument('created', false);
        $this->logOperation($created ? 'created' : 'updated', $event);
    }

    /**
     * Handle the model.deleted event.
     *
     * @param EventInterface $event The model event.
     * @return void
     */
    public function onModelDeleted(EventInterface $event): void
    {
        $this->logOperation('deleted', $event);
    }

    /**
     * Log a model operation.
     *
     * @param string         $operation The operation type.
     * @param EventInterface $event     The model event.
     * @return void
     */
    private function logOperation(string $operation, EventInterface $event): void
    {
        if ($this->logger === null) {
            return;
        }

        /** @var Model|null $model */
        $model = $event->getArgument('model');
        $table = $event->getArgument('table', 'unknown');

        $data = $model instanceof Model ? $model->toArray() : [];

        $this->logger->log(
            LogLevel::INFO,
            "Model {$operation} in table `{$table}`",
            [
                'table'  => $table,
                'data'   => $data,
                'action' => $operation,
            ]
        );
    }
}
