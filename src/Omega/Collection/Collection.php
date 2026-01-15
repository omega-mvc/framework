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

declare(strict_types=1);

namespace Omega\Collection;

use function array_chunk;
use function array_diff;
use function array_diff_assoc;
use function array_diff_key;
use function array_key_exists;
use function array_key_first;
use function array_reverse;
use function array_slice;
use function array_values;
use function arsort;
use function asort;
use function call_user_func;
use function ceil;
use function in_array;
use function is_array;
use function is_callable;
use function krsort;
use function ksort;
use function shuffle;
use function uasort;

use const INF;

/**
 * A mutable collection implementation providing a rich set of utility
 * methods for modifying and transforming data structures. Unlike
 * {@see CollectionImmutable}, this class allows items to be added,
 * updated, or removed after instantiation.
 *
 * The Collection class extends the shared immutable behavior and
 * iteration capabilities from {@see AbstractCollectionImmutable},
 * enhancing it with mutation operations such as setting values,
 * pushing elements, replacing the internal dataset, filtering items,
 * sorting, reducing, and flattening nested structures.
 *
 * This class is designed to offer a fluent interface: most methods
 * return `$this`, allowing expressive method chaining, e.g.:
 *
 * ```php
 * $collection = (new Collection(['a' => 1, 'b' => 2]))
 * ->set('c', 3)
 * ->filter(fn($value) => $value > 1)
 * ->sortDesc();
 * ```
 *
 * @category  Omega
 * @package   Collection
 * @link      https://omega-mvc.github.io
 * @author    Adriano Giovannini <agisoftt@gmail.com>
 * @copyright Copyright (c) 2025 - 2026 Adriano Giovannini (https://omega-mvc.github.io)
 * @license   https://www.gnu.org/licenses/gpl-3.0-standalone.html     GPL V3.0+
 * @version   2.0.0
 *
 * @template TKey of array-key
 * @template TValue
 *
 * @extends AbstractCollectionImmutable<TKey, TValue>
 *
 * @property mixed $buah_1
 * @property mixed $buah_8
 */
class Collection extends AbstractCollectionImmutable
{
    /**
     * Magic setter for assigning a value to a specific key in the collection.
     *
     * Allows property-like access:
     * ```php
     * $collection->foo = 'bar';
     * ```
     *
     * @param TKey   $name  The key to assign.
     * @param TValue $value The value to set.
     * @return void
     */
    public function __set(int|string $name, $value): void
    {
        $this->set($name, $value);
    }

    /**
     * Merge another collection into this collection by reference.
     *
     * Items from the given collection are appended to the current one
     * without cloning or copying.
     *
     * @param AbstractCollectionImmutable<TKey, TValue> $collection The collection to merge in.
     * @return $this
     */
    public function ref(AbstractCollectionImmutable $collection): self
    {
        $this->add($collection->collection);

        return $this;
    }

    /**
     * Remove all items from the collection.
     *
     * @return $this
     */
    public function clear(): self
    {
        $this->collection = [];

        return $this;
    }

    /**
     * Add multiple items to the collection.
     *
     * Existing keys will be overwritten. New keys are appended.
     *
     * @param array<TKey, TValue> $collection The items to merge in.
     * @return $this
     */
    public function add(array $collection): self
    {
        foreach ($collection as $key => $item) {
            $this->set($key, $item);
        }

        return $this;
    }

    /**
     * Remove an item from the collection by its key.
     *
     * @param TKey $name The key of the item to remove.
     * @return $this
     */
    public function remove(int|string $name): self
    {
        if ($this->has($name)) {
            unset($this->collection[$name]);
        }

        return $this;
    }

    /**
     * Set or replace a value for the given key.
     *
     * @param TKey   $name  The key to assign.
     * @param TValue $value The value to set.
     * @return $this
     */
    public function set(int|string $name, $value): self
    {
        parent::set($name, $value);

        return $this;
    }

    /**
     * Append a value to the collection without specifying a key.
     *
     * @param TValue $value The value to append.
     * @return $this
     */
    public function push($value): self
    {
        parent::push($value);

        return $this;
    }

    /**
     * Replace the entire dataset of the collection.
     *
     * The collection is first cleared, then rebuilt from the given array.
     *
     * @param array<TKey, TValue> $newCollection The new data to assign.
     * @return $this
     */
    public function replace(array $newCollection): self
    {
        $this->collection = [];
        foreach ($newCollection as $key => $item) {
            $this->set($key, $item);
        }

        return $this;
    }

    /**
     * Apply a transformation function to each item in the collection.
     *
     * The callback receives `(value, key)` and must return the new value.
     *
     * @param callable(TValue, TKey=): TValue $callable The mapping function.
     * @return $this
     */
    public function map(callable $callable): self
    {
        if (!is_callable($callable)) {
            return $this;
        }

        $newCollection = [];
        foreach ($this->collection as $key => $item) {
            $newCollection[$key] = call_user_func($callable, $item, $key);
        }

        $this->replace($newCollection);

        return $this;
    }

    /**
     * Filter the collection based on a condition callback.
     *
     * The callback receives `(value, key)` and must return true
     * to keep the item, or false to remove it.
     *
     * @param callable(TValue, TKey=): bool $condition The filter condition.
     * @return $this
     */
    public function filter(callable $condition): self
    {
        return $this->filterByCondition($condition);
    }

    /**
     * Reject items from the collection based on a given condition.
     *
     * The callback receives `(value, key)` and must return true for items
     * that should be removed.
     *
     * @param callable(TValue, TKey=): bool $condition The rejection condition.
     * @return $this
     */
    public function reject(callable $condition): self
    {
        return $this->filterByCondition($condition, true);
    }

    /**
     * Filter the collection using a callback, optionally inverted.
     *
     * This is the internal implementation for `filter()` and `reject()`.
     * When `$invert` is true, the logic is reversed.
     *
     * @internal
     *
     * @param callable(TValue, TKey=): bool $condition The condition callback.
     * @param bool $invert Whether to invert the filter logic.
     * @return $this
     */
    private function filterByCondition(callable $condition, bool $invert = false): self
    {
        $newCollection = [];
        foreach ($this->collection as $key => $item) {
            $result = $condition($item, $key);
            if ($invert ? !$result : $result) {
                $newCollection[$key] = $item;
            }
        }
        return $this->replace($newCollection);
    }

    /**
     * Reverse the order of items in the collection.
     *
     * @return $this
     */
    public function reverse(): self
    {
        return $this->replace(array_reverse($this->collection));
    }

    /**
     * Sort the collection in ascending order by value.
     *
     * Preserves keys.
     *
     * @return $this
     */
    public function sort(): self
    {
        asort($this->collection);

        return $this;
    }

    /**
     * Sort the collection in descending order by value.
     *
     * Preserves keys.
     *
     * @return $this
     */
    public function sortDesc(): self
    {
        arsort($this->collection);

        return $this;
    }

    /**
     * Sort the collection using a custom comparison callback.
     *
     * The callback receives `(value1, value2)` and must return -1, 0, or 1.
     * Keys are preserved.
     *
     * @param callable(TValue, TValue): int $callable The comparison function.
     * @return $this
     */
    public function sortBy(callable $callable): self
    {
        uasort($this->collection, $callable);

        return $this;
    }

    /**
     * Sort the collection using a custom comparison callback in descending order.
     *
     * Equivalent to `sortBy()` followed by `reverse()`.
     *
     * @param callable(TValue, TValue): int $callable The comparison function.
     * @return $this
     */
    public function sortByDesc(callable $callable): self
    {
        return $this->sortBy($callable)->reverse();
    }

    /**
     * Sort the collection by keys in ascending order.
     *
     * @return $this
     */
    public function sortKey(): self
    {
        ksort($this->collection);

        return $this;
    }

    /**
     * Sort the collection by keys in descending order.
     *
     * @return $this
     */
    public function sortKeyDesc(): self
    {
        krsort($this->collection);

        return $this;
    }

    /**
     * Create a deep clone of the collection.
     *
     * @return Collection<TKey, TValue> A new cloned instance.
     */
    public function clone(): Collection
    {
        return clone $this;
    }

    /**
     * Split the collection into chunks of a given size.
     *
     * Each chunk becomes an array element inside the collection. Keys inside
     * each chunk are preserved unless `$preserveKeys` is set to false.
     *
     * @param int $length The maximum size of each chunk.
     * @param bool $preserveKeys Whether to preserve the original keys.
     * @return $this
     */
    public function chunk(int $length, bool $preserveKeys = true): self
    {
        $this->collection = array_chunk($this->collection, $length, $preserveKeys);

        return $this;
    }

    /**
     * Split the collection into a given number of chunks.
     *
     * Calculates the chunk size based on the number of parts requested.
     * Internally uses `chunk()`.
     *
     * @param int $count Number of chunks to split into.
     * @param bool $preserveKeys Whether to preserve original keys.
     * @return $this
     */
    public function split(int $count, bool $preserveKeys = true): self
    {
        $length = (int) ceil($this->length() / $count);

        return $this->chunk($length, $preserveKeys);
    }

    /**
     * Remove items by key.
     *
     * Keeps all items except those whose keys appear in the given list.
     *
     * @param TKey[] $excepts Keys to exclude.
     * @return $this
     */
    public function except(array $excepts): self
    {
        $this->filter(fn ($item, $key) => !in_array($key, $excepts));

        return $this;
    }

    /**
     * Keep only items whose keys appear in the given list.
     *
     * All other items are removed.
     *
     * @param TKey[] $only Keys to include.
     * @return $this
     */
    public function only(array $only): self
    {
        /* @phpstan-ignore-next-line */
        $this->filter(fn ($item, $key) => in_array($key, $only));

        return $this;
    }

    /**
     * Flatten nested arrays or collections to a single-level collection.
     *
     * If `$depth` is provided, flattening stops once the depth limit is reached.
     * By default, it flattens recursively without limit.
     *
     * @param int|float $depth The depth to flatten to (use INF for unlimited).
     * @return $this
     */
    public function flatten(int|float $depth = INF): self
    {
        $flatten = $this->flattenRecursing($this->collection, $depth);
        $this->replace($flatten);

        return $this;
    }

    /**
     * Recursively flatten an array to the given depth.
     *
     * This is the internal implementation for `flatten()`.
     *
     * @internal
     *
     * @param array<TKey, TValue> $array The array to flatten.
     * @param float|int $depth Remaining flatten depth.
     * @return array<TKey, TValue>
     */
    private function flattenRecursing(array $array, float|int $depth = INF): array
    {
        $result = [];

        foreach ($array as $key => $item) {
            $item = $item instanceof Collection ? $item->all() : $item;

            if (!is_array($item)) {
                $result[$key] = $item;
            } else {
                $values = $depth === 1
                    ? array_values($item)
                    : $this->flattenRecursing($item, $depth - 1);

                foreach ($values as $keyDept => $value) {
                    $result[$keyDept] = $value;
                }
            }
        }

        return $result;
    }

    /**
     * Convert the collection into an immutable variant.
     *
     * @return CollectionImmutable<TKey, TValue> A new immutable collection instance.
     */
    public function immutable(): CollectionImmutable
    {
        return new CollectionImmutable($this->collection);
    }

    /**
     * Remove an item using ArrayAccess syntax.
     *
     * {@inheritdoc}
     */
    public function offsetUnset($offset): void
    {
        $this->remove($offset);
    }

    /**
     * Shuffle the order of the items while preserving keys.
     *
     * @return $this
     */
    public function shuffle(): self
    {
        $items = $this->collection;
        $keys  = $this->keys();
        shuffle($keys);
        $reordered = [];
        foreach ($keys as $key) {
            $reordered[$key] = $items[$key];
        }

        return $this->replace($reordered);
    }

    /**
     * Convert each item into a key-value pair and rebuild the collection.
     *
     * The callback must return an associative array containing exactly
     * **one key/value pair**. The returned key becomes the new collection key.
     *
     * @template TKeyItem of array-key
     * @template TValueItem
     * @param callable(TValue, TKey=): array<TKeyItem, TValueItem> $callable
     * @return $this
     */
    public function assocBy(callable $callable): self
    {
        /** @var array<TKeyItem, TValueItem> $newCollectionn */
        $newCollection = [];
        foreach ($this->collection as $key => $item) {
            $arrayAssoc          = $callable($item, $key);
            $key                 = array_key_first($arrayAssoc);
            $newCollection[$key] = $arrayAssoc[$key];
        }

        return $this->replace($newCollection);
    }

    /**
     * Reduce the collection to a single accumulated value.
     *
     * @template TCarry
     * @param callable(TCarry|null, TValue): TCarry $callable Accumulator callback.
     * @param TCarry|null $carry Initial accumulator value.
     * @return TCarry|null The reduced value.
     */
    public function reduce(callable $callable, $carry = null): mixed
    {
        foreach ($this->collection as $item) {
            $carry = $callable($carry, $item);
        }

        return $carry;
    }

    /**
     * Take the first or last N items of the collection.
     *
     * If the limit is positive, the first N items are taken.
     * If the limit is negative, the last N items are taken.
     *
     * @param int $limit Number of items to take.
     * @return $this
     */
    public function take(int $limit): self
    {
        if ($limit < 0) {
            return $this->replace(
                array_slice($this->collection, $limit, abs($limit))
            );
        }

        return $this->replace(
            array_slice($this->collection, 0, $limit)
        );
    }

    /**
     * Remove the given values from the collection.
     *
     * @param array<TKey, TValue> $collection Values to exclude.
     * @return $this
     */
    public function diff(array $collection): self
    {
        return $this->replace(
            array_diff($this->collection, $collection)
        );
    }

    /**
     * Remove the items whose keys are present in the given array.
     *
     * @param array<TKey, TValue> $collection Keys to exclude.
     * @return $this
     */
    public function diffKeys(array $collection): self
    {
        return $this->replace(
            array_diff_key($this->collection, $collection)
        );
    }

    /**
     * Remove the items whose key/value pairs are present in the given array.
     *
     * @param array<TKey, TValue> $collection Key/value pairs to exclude.
     * @return $this
     */
    public function diffAssoc(array $collection): self
    {
        return $this->replace(
            array_diff_assoc($this->collection, $collection)
        );
    }

    /**
     * Keep only items that are not present in the current collection.
     *
     * @param array<TKey, TValue> $collection Values to compare.
     * @return $this
     */
    public function complement(array $collection): self
    {
        return $this->replace(
            array_diff($collection, $this->collection)
        );
    }

    /**
     * Keep only keys that are not present in the current collection.
     *
     * @param array<TKey, TValue> $collection Keys to compare.
     * @return $this
     */
    public function complementKeys(array $collection): self
    {
        return $this->replace(
            array_diff_key($collection, $this->collection)
        );
    }

    /**
     * Keep only key/value pairs that are not present in the current collection.
     *
     * @param array<TKey, TValue> $collection Key/value pairs to compare.
     * @return $this
     */
    public function complementAssoc(array $collection): self
    {
        return $this->replace(
            array_diff_assoc($collection, $this->collection)
        );
    }

    /**
     * Filter items where a given key matches a comparison operator.
     *
     * @param int|string $key The field to compare within each item.
     * @param string $operator
     * @param mixed $value The value to compare against.
     * @return $this
     */
    public function where(int|string $key, string $operator, mixed $value): self
    {
        if ('=' === $operator || '==' === $operator) {
            return $this->filter(fn ($TValue) => array_key_exists($key, $TValue) && $TValue[$key] == $value);
        }
        if ('===' === $operator) {
            return $this->filter(fn ($TValue) => array_key_exists($key, $TValue) && $TValue[$key] === $value);
        }
        if ('!=' === $operator) {
            return $this->filter(fn ($TValue) => array_key_exists($key, $TValue) && $TValue[$key] != $value);
        }
        if ('!==' === $operator) {
            return $this->filter(fn ($TValue) => array_key_exists($key, $TValue) && $TValue[$key] !== $value);
        }
        if ('>' === $operator) {
            return $this->filter(fn ($TValue) => array_key_exists($key, $TValue) && $TValue[$key] > $value);
        }
        if ('>=' === $operator) {
            return $this->filter(fn ($TValue) => array_key_exists($key, $TValue) && $TValue[$key] >= $value);
        }
        if ('<' === $operator) {
            return $this->filter(fn ($TValue) => array_key_exists($key, $TValue) && $TValue[$key] < $value);
        }
        if ('<=' === $operator) {
            return $this->filter(fn ($TValue) => array_key_exists($key, $TValue) && $TValue[$key] <= $value);
        }

        return $this->replace([]);
    }

    /**
     * Filter items where the value of the given key is in the provided range.
     *
     * @param TKey $key The field to match.
     * @param array<TValue> $range Accepted values.
     * @return $this
     */
    public function whereIn(int|string $key, array $range): self
    {
        return $this->filter(fn ($TValue) => array_key_exists($key, $TValue) && in_array($TValue[$key], $range));
    }

    /**
     * Filter items where the value of the given key is not in the provided range.
     *
     * @param TKey $key The field to match.
     * @param array<TValue> $range Values to exclude.
     * @return $this
     */
    public function whereNotIn(int|string $key, array $range): self
    {
        return $this->filter(
            fn ($TValue) => array_key_exists($key, $TValue) && false === in_array($TValue[$key], $range)
        );
    }
}
