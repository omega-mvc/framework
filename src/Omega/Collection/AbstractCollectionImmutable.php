<?php

/**
 * Part of Omega - Collection Package.
 *
 * @link      https://omega-mvc.github.io
 * @author    Adriano Giovannini <agisoftt@gmail.com>
 * @copyright Copyright (c) 2025 - 2026 Adriano Giovannini (https://omega-mvc.github.io)
 * @license   https://www.gnu.org/licenses/gpl-3.0-standalone.html     GPL V3.0+
 * @version   2.0.0
 */

/** @noinspection PhpMissingParamTypeInspection Don't touch this suppression. */
/** @noinspection PhpMixedReturnTypeCanBeReducedInspection Don't touch this suppression. */

declare(strict_types=1);

namespace Omega\Collection;

use ArrayIterator;
use ReturnTypeWillChange;
use Traversable;

use function array_column;
use function array_count_values;
use function array_key_exists;
use function array_key_first;
use function array_key_last;
use function array_keys;
use function array_rand;
use function array_slice;
use function array_sum;
use function array_values;
use function call_user_func;
use function count;
use function current;
use function in_array;
use function is_array;
use function is_null;
use function is_object;
use function json_encode;
use function max;
use function min;
use function next;
use function prev;
use function var_dump;

/**
 * Base immutable collection implementation.
 *
 * This abstract class provides a standardized foundation for collection-like
 * data structures where stored values are not intended to be altered externally.
 * It offers read-only access methods, iteration support, value extraction helpers
 * and utilities common for collection management.
 *
 * The collection may contain any type of values, and keys are preserved as provided.
 *
 * @category  Omega
 * @package   Collection
 * @link      https://omega-mvc.github.io
 * @author    Adriano Giovannini <agisoftt@gmail.com>
 * @copyright Copyright (c) 2025 - 2026 Adriano Giovannini (https://omega-mvc.github.io)
 * @license   https://www.gnu.org/licenses/gpl-3.0-standalone.html     GPL V3.0+
 * @version   2.0.0
 *
 *
 * @template TKey of array-key
 * @template TValue
 *
 * @implements CollectionInterface<TKey, TValue>
 */
abstract class AbstractCollectionImmutable implements CollectionInterface
{
    /** @var array<TKey, TValue> Internal items' storage. */
    protected array $collection = [];

    /**
     * Create a new immutable collection instance.
     *
     * @param iterable<TKey, TValue> $collection Initial collection items.
     * @return void
     */
    public function __construct(array $collection)
    {
        foreach ($collection as $key => $item) {
            $this->set($key, $item);
        }
    }

    /**
     * Magic property accessor.
     *
     * Allows accessing collection items using object property syntax,
     * effectively proxying the lookup to {@see self::get()}.
     *
     * @param TKey $name The item key to retrieve.
     * @return TValue|null The item value if it exists, otherwise null.
     */
    public function __get(int|string $name)
    {
        return $this->get($name);
    }

    /**
     * Retrieve all items in the collection as an array.
     *
     * This returns the raw internal storage. Consumers should treat the array
     * as read-only to preserve immutability guarantees.
     *
     * @return array<TKey, TValue>
     */
    public function all(): array
    {
        return $this->collection;
    }

    /**
     * Convert the collection to a plain array representation.
     *
     * This method fulfills the {@see CollectionInterface::toArray()} contract
     * and simply returns the internal backing array.
     *
     * @return array<TKey, TValue>
     */
    public function toArray(): array
    {
        return $this->collection;
    }

    /**
     * Retrieve an item by key, returning a default value if not found.
     *
     * @template TGetDefault
     * @param TKey|null $name The key of the item to retrieve.
     * @param TGetDefault|null $default The default value to return if the key is not present.
     * @return TValue|TGetDefault|null The retrieved value or the provided default.
     */
    public function get(int|string|null $name, $default = null): mixed
    {
        return $this->collection[$name] ?? $default;
    }

    /**
     * Set a value in the collection at the given key.
     *
     * This method is protected because immutability rules are enforced at the
     * concrete class level. Mutable or clone-based mutation implementations may override usage.
     *
     * @param TKey $name The key to set.
     * @param TValue $value The value to assign.
     * @return $this
     */
    protected function set(int|string $name, $value): self
    {
        $this->collection[$name] = $value;

        return $this;
    }

    /**
     * Append a value to the end of the collection.
     *
     * Similar to {@see self::set()} but automatically assigns an incremental key.
     * Visibility is protected to allow controlled mutation in extending classes.
     *
     * @param TValue $value The value to append.
     * @return $this
     */
    protected function push($value): self
    {
        $this->collection[] = $value;

        return $this;
    }

    /**
     * Determine whether the collection contains the given key.
     *
     * @param TKey|null $key The key to check for existence.
     * @return bool True if the key exists, false otherwise.
     */
    public function has(int|string|null $key): bool
    {
        return array_key_exists($key, $this->collection);
    }

    /**
     * Determine whether the collection contains the given value.
     *
     * @param TValue $item The value to search for.
     * @param bool $strict Whether to use strict comparison during lookup.
     * @return bool True if the value exists, false otherwise.
     */
    public function contain($item, bool $strict = false): bool
    {
        return in_array($item, $this->collection, $strict);
    }

    /**
     * Get all keys from the collection.
     *
     * @return TKey[] Array of keys.
     */
    public function keys(): array
    {
        return array_keys($this->collection);
    }

    /**
     * Get all values from the collection, discarding original keys.
     *
     * @return TValue[] Array of values indexed numerically.
     */
    public function items(): array
    {
        return array_values($this->collection);
    }

    /**
     * Extract a specific value from each item in the collection.
     *
     * This method retrieves a value from each item using the provided key.
     * If a second key is specified, the results will be keyed by that value.
     * Works with both arrays and objects.
     *
     * @param TKey $value The property or array key to use as the extracted value.
     * @param TKey|null $key Optional property or key to use as the index for the returned array.
     * @return array<TKey, TValue> The resulting mapped array of extracted values.
     */
    public function pluck(int|string $value, int|string|null $key = null): array
    {
        $results = [];

        foreach ($this->collection as $item) {
            $itemValue = is_array($item) ? $item[$value] : $item->{$value};

            if (is_null($key)) {
                $results[] = $itemValue;
                continue;
            }

            $itemKey           = is_array($item) ? $item[$key] : $item->{$key};
            $results[$itemKey] = $itemValue;
        }

        return $results;
    }

    /**
     * Count the number of elements in the collection.
     *
     * @return int Total number of stored items.
     */
    public function count(): int
    {
        return count($this->collection);
    }

    /**
     * Count items that satisfy the given condition.
     *
     * The callback receives the item and its key. Each time the callback returns true,
     * the counter is incremented.
     *
     * @param callable(TValue, TKey=): bool $condition The condition to evaluate for each item.
     * @return int The number of items that satisfy the condition.
     */
    public function countIf(callable $condition): int
    {
        $count = 0;
        foreach ($this->collection as $key => $item) {
            $doSomething = call_user_func($condition, $item, $key);

            $count += $doSomething === true ? 1 : 0;
        }

        return $count;
    }

    /**
     * Count the frequency of each unique value in the collection.
     *
     * Values are compared loosely unless strict comparison is ensured by the data structure itself.
     *
     * @return array<TKey, int> An associative array where keys are values and values are counts.
     */
    public function countBy(): array
    {
        return array_count_values($this->collection);
    }

    /**
     * Iterate through each item in the collection, executing a callback.
     *
     * The callback receives the item and its key. Returning `false` from the callback stops iteration early.
     *
     * @param callable(TValue, TKey=): (bool|void) $callable The callback to execute.
     * @return $this
     */
    public function each(callable $callable): self
    {
        foreach ($this->collection as $key => $item) {
            $doSomething = call_user_func($callable, $item, $key);

            if (false === $doSomething) {
                break;
            }
        }

        return $this;
    }

    /**
     * Dump the contents of the collection for debugging purposes.
     *
     * Outputs the underlying array structure using var_dump() and returns the collection
     * instance for method chaining.
     *
     * @return $this
     */
    public function dump(): self
    {
        var_dump($this->collection);

        return $this;
    }

    /**
     * Determine whether at least one item in the collection satisfies the callback condition.
     *
     * The callback receives each item and its key. Iteration stops as soon as one condition is satisfied.
     *
     * @param callable(TValue, TKey=): bool $condition The condition to evaluate.
     * @return bool True if any item satisfies the condition, otherwise false.
     */
    public function some(callable $condition): bool
    {
        foreach ($this->collection as $key => $item) {
            $call = call_user_func($condition, $item, $key);

            if ($call === true) {
                return true;
            }
        }

        return false;
    }

    /**
     * Determine whether all items in the collection satisfy the callback condition.
     *
     * The callback receives each item and its key. If any callback returns false,
     * the method immediately returns false.
     *
     * @param callable(TValue, TKey=): bool $condition The condition to evaluate.
     * @return bool True if all items satisfy the condition, otherwise false.
     */
    public function every(callable $condition): bool
    {
        foreach ($this->collection as $key => $item) {
            $call = call_user_func($condition, $item, $key);

            if ($call === false) {
                return false;
            }
        }

        return true;
    }

    /**
     * Convert the collection to a JSON string representation.
     *
     * @return string The JSON-encoded representation of the collection.
     */
    public function json(): string
    {
        return json_encode($this->collection);
    }

    /**
     * Retrieve the first item in the collection.
     *
     * If the collection is empty, the provided default value is returned.
     *
     * @template TGetDefault
     * @param TGetDefault|null $default The value to return if the collection has no items.
     * @return TValue|TGetDefault|null The first item or the default value.
     */
    public function first($default = null): mixed
    {
        $key = array_key_first($this->collection) ?? 0;

        return $this->collection[$key] ?? $default;
    }

    /**
     * Retrieve a slice of the first N items in the collection.
     *
     * @param positive-int $take The number of items to retrieve.
     * @return array<TKey, TValue> A new array containing the requested items.
     */
    public function firsts(int $take): array
    {
        return array_slice($this->collection, 0, (int) $take);
    }

    /**
     * Get the last item in the collection.
     *
     * If the collection is empty, the provided default value is returned instead.
     *
     * @template TGetDefault
     * @param TGetDefault|null $default The value to return when the collection is empty.
     * @return TValue|TGetDefault|null The last item or the default value.
     */
    public function last($default = null): mixed
    {
        $key = array_key_last($this->collection);

        return $this->collection[$key] ?? $default;
    }

    /**
     * Get a slice containing the last N items of the collection.
     *
     * @param positive-int $take The number of items to return.
     * @return array<TKey, TValue> A new array containing the requested trailing items.
     */
    public function lasts(int $take): array
    {
        return array_slice($this->collection, -$take, (int) $take);
    }

    /**
     * Get the first key of the collection.
     *
     * @return string|int|null The first key, or null if the collection is empty.
     */
    public function firstKey(): string|int|null
    {
        return array_key_first($this->collection);
    }

    /**
     * Get the last key of the collection.
     *
     * @return string|int|null The last key, or null if the collection is empty.
     */
    public function lastKey(): string|int|null
    {
        return array_key_last($this->collection);
    }

    /**
     * Get the current item in the collection's internal pointer position.
     *
     * @return TValue The current item.
     */
    public function current()
    {
        return current($this->collection);
    }

    /**
     * Advance the internal pointer and return the next item.
     *
     * @return TValue The next item.
     */
    public function next()
    {
        return next($this->collection);
    }

    /**
     * Move the internal pointer backward and return the previous item.
     *
     * @return TValue The previous item.
     */
    public function prev()
    {
        return prev($this->collection);
    }

    /**
     * Retrieve a random item from the collection.
     *
     * @return TValue The randomly selected item.
     */
    public function rand(): mixed
    {
        $rand = array_rand($this->collection);

        return $this->get($rand);
    }

    /**
     * Determine whether the collection contains no items.
     *
     * @return bool True if the collection is empty, otherwise false.
     */
    public function isEmpty(): bool
    {
        return empty($this->collection);
    }

    /**
     * Get the total number of items in the collection.
     *
     * Alias of `count()` for semantic clarity.
     *
     * @return int The number of items in the collection.
     */
    public function length(): int
    {
        return count($this->collection);
    }

    /**
     * Calculate the sum of all numeric values in the collection.
     *
     * Non-numeric values will be ignored by `array_sum()`.
     *
     * @return int The resulting sum of all numeric items.
     */
    public function sum(): int
    {
        return array_sum($this->collection);
    }

    /**
     * Calculate the average value of the collection.
     *
     * This method divides the sum of all values by the total item count.
     * If the collection is empty, a division by zero error may occur,
     * so ensure the collection contains items before calling.
     *
     * @return int The average (mean) of all items.
     */
    public function avg(): int
    {
        return $this->sum() / $this->count();
    }

    /**
     * Find the maximum value in the collection.
     *
     * When a key is provided, the method assumes the collection is made of
     * arrays or array-like items and extracts the given column before evaluating.
     *
     * @param int|string|null $key The optional key or column to evaluate.
     * @return int The highest value found.
     */
    public function max(int|string|null $key = null): int
    {
        return max(array_column($this->collection, $key));
    }

    /**
     * Find the minimum value in the collection.
     *
     * When a key is provided, the method assumes the collection is made of
     * arrays or array-like items and extracts the given column before evaluating.
     *
     * @param int|string|null $key The optional key or column to evaluate.
     * @return int The lowest value found.
     */
    public function min(int|string|null $key = null): int
    {
        return min(array_column($this->collection, $key));
    }

    /**
     * Check whether the given key exists in the collection.
     *
     * @param TKey $offset
     * @return bool True if the key exists, otherwise false.
     */
    public function offsetExists(mixed $offset): bool
    {
        return $this->has($offset);
    }

    /**
     * Retrieve the value associated with the given key.
     *
     * @param TKey $offset
     * @return TValue|null The value for the key, or null if it does not exist.
     */
    #[ReturnTypeWillChange]
    public function offsetGet(mixed $offset): mixed
    {
        return $this->__get($offset);
    }

    /**
     * Set a value in the collection by key.
     *
     * Note: While this modifies the internal storage, derived implementations
     * can override to enforce immutability (e.g., by throwing an exception).
     *
     * @param TKey $offset
     * @param TValue $value
     * @return void
     */
    public function offsetSet(mixed $offset, mixed $value): void
    {
        $this->set($offset, $value);
    }

    /**
     * Remove a value from the collection by key.
     *
     * Note: Typically disabled for immutable collections. Override to throw
     * ImmutableCollectionException if immutability must be strictly enforced.
     *
     * @param TKey $offset
     * @return void
     */
    public function offsetUnset($offset): void
    {
    }

    /**
     * Retrieve an external iterator for the collection.
     *
     * @return Traversable<TKey, TValue>
     */
    public function getIterator(): Traversable
    {
        return new ArrayIterator($this->all());
    }

    /**
     * Create a deep copy when the collection is cloned.
     *
     * Nested arrays and objects are recursively cloned, ensuring that
     * no internal elements are shared by reference.
     *
     * @return void
     */
    public function __clone()
    {
        $this->collection = $this->deepClone($this->collection);
    }

    /**
     * Recursively create a deep clone of the given collection.
     *
     * Arrays are cloned element-by-element, and objects are cloned
     * using PHP's native `clone` keyword.
     *
     * @param array<TKey, TValue> $collection The collection to deep clone.
     * @return array<TKey, TValue> The cloned collection.
     */
    protected function deepClone(array $collection): array
    {
        $clone = [];

        foreach ($collection as $key => $value) {
            if (is_array($value)) {
                $clone[$key] = $this->deepClone($value);
                continue;
            }

            if (is_object($value)) {
                $clone[$key] = clone $value;
                continue;
            }

            $clone[$key] = $value;
        }

        return $clone;
    }
}
