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

namespace Omega\Event\Dispatcher;

use Omega\Event\EventInterface;
use Omega\Event\ListenersPriorityQueue;
use Omega\Event\Priority;
use Omega\Event\SubscriberInterface;

use function array_keys;
use function array_shift;
use function count;
use function is_array;
use function is_callable;
use function is_int;

/**
 * Central event dispatcher responsible for managing and executing event listeners.
 *
 * The Dispatcher acts as the core runtime component of the event system.
 * It allows registration of listeners and subscribers, and ensures execution
 * in priority order for each dispatched event.
 *
 * Listeners are stored per event name and executed in a deterministic order
 * based on their assigned priority.
 *
 * The dispatcher also supports:
 * - Subscriber-based registration (multiple listeners per class)
 * - Listener priority queues
 * - Event propagation stopping
 *
 * It does not manage event creation logic; it only orchestrates execution.
 *
 * @category   Omega
 * @package    Event
 * @subpackage Dispatcher
 * @link       https://omega-mvc.github.io
 * @author     Adriano Giovannini <agisoftt@gmail.com>
 * @copyright  Copyright (c) 2025 - 2026 Adriano Giovannini (https://omega-mvc.github.io)
 * @license   https://www.gnu.org/licenses/gpl-3.0-standalone.html     GPL V3.0+
 * @version    2.0.0
 */
class Dispatcher implements DispatcherInterface
{
    /**
     * Internal registry of event listeners grouped by event name.
     *
     * Each event name maps to a priority queue that stores callable listeners
     * sorted by execution priority.
     *
     * @var array<string, ListenersPriorityQueue>
     */
    protected array $listeners = [];

    /**
     * {@inheritdoc}
     */
    public function addListener(string $eventName, callable $callback, int $priority = 0): bool
    {
        if (!isset($this->listeners[$eventName])) {
            $this->listeners[$eventName] = new ListenersPriorityQueue();
        }

        $this->listeners[$eventName]->add($callback, $priority);

        return true;
    }

    /**
     * Retrieves the priority of a registered listener for a specific event.
     *
     * If the listener is not registered, null is returned.
     *
     * @param string $eventName The event name.
     * @param callable(EventInterface): void $callback The listener callback.
     * @return int|null The priority of the listener or null if not found.
     */
    public function getListenerPriority(string $eventName, callable $callback): ?int
    {
        if (!isset($this->listeners[$eventName])) {
            return null;
        }

        $priority = $this->listeners[$eventName]->getPriority($callback);

        return is_int($priority) ? $priority : null;
    }

    /**
     * {@inheritdoc}
     */
    public function getListeners(?string $event = null): array
    {
        if ($event !== null) {
            if (isset($this->listeners[$event])) {
                return $this->listeners[$event]->getAll();
            }

            return [];
        }

        $dispatcherListeners = $this->collectGroupedListeners(array_keys($this->listeners));

        return $dispatcherListeners;
    }

    /**
     * Flattens the listener queues into an associative array of listener lists.
     *
     * @param list<int|string> $eventNames The registered event names.
     * @return array<string, list<callable(EventInterface): void>>
     */
    private function collectGroupedListeners(array $eventNames): array
    {
        $eventName = array_shift($eventNames);

        if ($eventName === null) {
            return [];
        }

        return [
            $eventName => $this->listeners[$eventName]->getAll(),
            ...$this->collectGroupedListeners($eventNames),
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function hasListener(callable $callback, ?string $eventName = null): bool
    {
        if ($eventName) {
            if (isset($this->listeners[$eventName])) {
                return $this->listeners[$eventName]->has($callback);
            }

            return false;
        }

        return $this->hasInQueues(array_keys($this->listeners), $callback);
    }

    private function hasInQueues(array $eventNames, callable $callback): bool
    {
        $eventName = array_shift($eventNames);

        if ($eventName === null) {
            return false;
        }

        if ($this->listeners[$eventName]->has($callback)) {
            return true;
        }

        return $this->hasInQueues($eventNames, $callback);
    }

    /**
     * {@inheritdoc}
     */
    public function removeListener(string $eventName, callable $listener): void
    {
        if (isset($this->listeners[$eventName])) {
            $this->listeners[$eventName]->remove($listener);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function clearListeners(?string $event = null): static
    {
        if ($event) {
            if (isset($this->listeners[$event])) {
                unset($this->listeners[$event]);
            }
        } else {
            $this->listeners = [];
        }

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function countListeners(string $event): int
    {
        return isset($this->listeners[$event]) ? count($this->listeners[$event]) : 0;
    }

    /**
     * {@inheritdoc}
     */
    public function addSubscriber(SubscriberInterface $subscriber): void
    {
        $this->registerSubscriptions($this->collectSubscriptions($subscriber, $subscriber->getSubscribedEvents()), true);
    }

    /**
     * {@inheritdoc}
     */
    public function removeSubscriber(SubscriberInterface $subscriber): void
    {
        $this->registerSubscriptions($this->collectSubscriptions($subscriber, $subscriber->getSubscribedEvents()), false);
    }

    /**
     * Registers or unregisters a flat list of subscriber subscriptions.
     *
     * @param list<array{0: string, 1: array<int, mixed>|callable, 2: int}> $subscriptions The resolved subscriptions.
     * @param bool $register True to register, false to unregister.
     */
    private function registerSubscriptions(array $subscriptions, bool $register): void
    {
        $subscription = array_shift($subscriptions);

        if ($subscription === null) {
            return;
        }

        if ($register) {
            $this->addListener($subscription[0], $subscription[1], $subscription[2]);
        } else {
            $this->removeListener($subscription[0], $subscription[1]);
        }

        $this->registerSubscriptions($subscriptions, $register);
    }

    /**
     * Resolves the subscribed events of a subscriber into a flat list of registrations.
     *
     * Entries whose listener is empty or not callable are skipped.
     *
     * @return list<array{0: string, 1: array<int, mixed>|callable, 2: int}>
     */
    private function collectSubscriptions(SubscriberInterface $subscriber, array $events): array
    {
        $eventName = array_key_first($events);

        if ($eventName === null) {
            return [];
        }

        $params = $events[$eventName];

        unset($events[$eventName]);

        $subscription = $this->resolveSubscription($subscriber, $params);

        if ($subscription === null) {
            return $this->collectSubscriptions($subscriber, $events);
        }

        return [[$eventName, $subscription[0], $subscription[1]], ...$this->collectSubscriptions($subscriber, $events)];
    }

    /**
     * @return array{0: callable, 1: int}|null
     */
    private function resolveSubscription(SubscriberInterface $subscriber, array|string $params): ?array
    {
        if (is_array($params)) {
            $listener = [$subscriber, $params[0]];

            if ($params[0] === '') {
                return null;
            }

            if (!is_callable($listener)) {
                return null;
            }

            $priority = $params[1] ?? Priority::NORMAL;

            return [$listener, $priority instanceof Priority ? $priority->value : $priority];
        }

        $listener = [$subscriber, $params];

        if ($params === '') {
            return null;
        }

        if (!is_callable($listener)) {
            return null;
        }

        return [$listener, 0];
    }

    /**
     * {@inheritdoc}
     */
    public function dispatch(EventInterface $event): EventInterface
    {
        if (isset($this->listeners[$event->getName()])) {
            return $this->dispatchQueue($this->listeners[$event->getName()]->getAll(), $event);
        }

        return $event;
    }

    /**
     * Executes the given listeners in order until the event is stopped.
     *
     * @param list<callable(EventInterface): void> $listeners The listeners to execute.
     * @param EventInterface $event The event being dispatched.
     * @return EventInterface The dispatched, possibly stopped, event.
     */
    private function dispatchQueue(array $listeners, EventInterface $event): EventInterface
    {
        if ([] === $listeners) {
            return $event;
        }

        if ($event->isStopped()) {
            return $event;
        }

        $listener = array_shift($listeners);

        $listener($event);

        return $this->dispatchQueue($listeners, $event);
    }
}
