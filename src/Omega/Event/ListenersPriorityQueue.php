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

namespace Omega\Event;

use ArrayIterator;
use Countable;
use IteratorAggregate;
use ReturnTypeWillChange;

use function array_keys;
use function array_map;
use function array_merge;
use function array_search;
use function array_shift;
use function array_sum;
use function count;
use function krsort;

/**
 * Priority-based listener queue.
 *
 * This class stores event listeners grouped by priority level and ensures
 * they are executed in descending priority order when retrieved.
 *
 * Internally, listeners are bucketed by priority (integer keys), allowing
 * efficient insertion and ordered retrieval without repeated sorting on insert.
 *
 * Higher priority values are executed before lower ones.
 *
 * @category  Omega
 * @package   Event
 * @link      https://omega-mvc.github.io
 * @author    Adriano Giovannini <agisoftt@gmail.com>
 * @copyright Copyright (c) 2026 Adriano Giovannini
 * @license   https://www.gnu.org/licenses/gpl-3.0-standalone.html     GPL V3.0+
 * @version   2.0.0
 *
 * @implements IteratorAggregate<int, callable(EventInterface): void>
 */
final class ListenersPriorityQueue implements IteratorAggregate, Countable
{
    /**
     * Internal storage of listeners grouped by priority level.
     *
     * The array structure is:
     * [
     *   priority => [callable, callable, ...],
     * ]
     *
     * Higher numeric priority values have precedence.
     *
     * @var array<int, array<int, callable(EventInterface): void>>
     */
    private array $listeners = [];

    /**
     * Adds a listener to the queue under the given priority.
     *
     * Multiple listeners can share the same priority level.
     * Listeners are appended in insertion order within the same priority bucket.
     *
     * @param callable(EventInterface): void $callback The event listener to register.
     * @param int $priority Execution priority (higher values run first).
     * @return ListenersPriorityQueue
     */
    public function add(callable $callback, int $priority): ListenersPriorityQueue
    {
        $this->listeners[$priority][] = $callback;

        return $this;
    }

    /**
     * Removes a listener from the queue if present.
     *
     * The listener is searched across all priority levels.
     *
     * @param callable(EventInterface): void $callback The listener to remove.
     * @return ListenersPriorityQueue
     */
    public function remove(callable $callback): ListenersPriorityQueue
    {
        $this->removeInBuckets(array_keys($this->listeners), $callback);

        return $this;
    }

    private function removeInBuckets(array $priorities, callable $callback): void
    {
        $priority = array_shift($priorities);

        if ($priority === null) {
            return;
        }

        $key = array_search($callback, $this->listeners[$priority], true);

        if ($key !== false) {
            unset($this->listeners[$priority][$key]);
        }

        $this->removeInBuckets($priorities, $callback);
    }

    /**
     * Checks whether a listener is registered in the queue.
     *
     * The search is performed across all priority levels.
     *
     * @param callable(EventInterface): void $callback The listener to check.
     * @return bool True if the listener exists, false otherwise.
     */
    public function has(callable $callback): bool
    {
        return $this->hasInBuckets(array_keys($this->listeners), $callback);
    }

    private function hasInBuckets(array $priorities, callable $callback): bool
    {
        $priority = array_shift($priorities);

        if ($priority === null) {
            return false;
        }

        if (array_search($callback, $this->listeners[$priority], true) !== false) {
            return true;
        }

        return $this->hasInBuckets($priorities, $callback);
    }

    /**
     * Returns the priority assigned to a given listener.
     *
     * If the listener exists in multiple buckets (should not normally happen),
     * the first matching priority encountered is returned.
     *
     * @param callable(EventInterface): void $callback The listener to inspect.
     * @param mixed $default Value returned if the listener is not found.
     * @return mixed The priority level or the default value.
     */
    public function getPriority(callable $callback, mixed $default = null): mixed
    {
        return $this->getPriorityInBuckets(array_keys($this->listeners), $callback, $default);
    }

    private function getPriorityInBuckets(array $priorities, callable $callback, mixed $default): mixed
    {
        $priority = array_shift($priorities);

        if ($priority === null) {
            return $default;
        }

        if (array_search($callback, $this->listeners[$priority], true) !== false) {
            return $priority;
        }

        return $this->getPriorityInBuckets($priorities, $callback, $default);
    }

    /**
     * Returns all listeners sorted by priority (descending).
     *
     * Listeners are flattened into a single list ordered by:
     * 1. Priority (higher first)
     * 2. Insertion order within the same priority
     *
     * @return list<callable(EventInterface): void> Ordered list of listeners.
     */
    public function getAll(): array
    {
        if (empty($this->listeners)) {
            return [];
        }

        krsort($this->listeners);

        return array_merge(...$this->listeners);
    }

    /**
     * Returns an iterator over all listeners in execution order.
     *
     * @return ArrayIterator<int, callable(EventInterface): void> Iterator of ordered listeners.
     */
    #[ReturnTypeWillChange]
    public function getIterator(): ArrayIterator
    {
        return new ArrayIterator($this->getAll());
    }

    /**
     * Counts all registered listeners across all priority levels.
     *
     * @return int Total number of listeners in the queue.
     */
    #[ReturnTypeWillChange]
    public function count(): int
    {
        return array_sum(array_map('count', $this->listeners));
    }
}
