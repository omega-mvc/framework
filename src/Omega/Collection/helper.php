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

use function array_key_exists;
use function array_slice;
use function count;
use function explode;
use function implode;

/**
 * This file contains helper functions to work with Collection and arrays:
 *
 * 1. `collection()` - creates a mutable Collection instance from an iterable.
 * 2. `collection_immutable()` - creates an immutable Collection instance from an iterable.
 * 3. `data_get()` - retrieves a value from a nested array using "dot" notation,
 *    supporting wildcards (*) and a default value when the key does not exist.
 *
 * These helpers provide convenient shortcuts for interacting with collections
 * in a functional and chainable style, improving code readability and reducing boilerplate.
 *
 * @category  Omega
 * @package   Collection
 * @link      https://omega-mvc.github.io
 * @author    Adriano Giovannini <agisoftt@gmail.com>
 * @copyright Copyright (c) 2025 - 2026 Adriano Giovannini (https://omega-mvc.github.io)
 * @license   https://www.gnu.org/licenses/gpl-3.0-standalone.html     GPL V3.0+
 * @version   2.0.0
 */

if (!function_exists('collection')) {
    /**
     * Create a new mutable collection instance.
     *
     * @template TKey of array-key
     * @template TValue
     * @param iterable<TKey, TValue> $collection Initial items for the collection
     * @return Collection<TKey, TValue> Returns a mutable Collection instance
     */
    function collection(iterable $collection = []): Collection
    {
        return new Collection($collection);
    }
}

if (!function_exists('collection_immutable')) {
    /**
     * Create a new immutable collection instance.
     *
     * @template TKey of array-key
     * @template TValue
     * @param iterable<TKey, TValue> $collection Initial items for the collection
     * @return Collection<TKey, TValue> Returns an immutable Collection instance
     */
    function collection_immutable(iterable $collection = []): Collection
    {
        return new Collection($collection);
    }
}

if (!function_exists('data_get')) {
    /**
     * Retrieve a value from a nested array using "dot" notation.
     *
     * Supports wildcard segments (`*`) to return multiple values from arrays of arrays.
     * If the key does not exist, the specified default value is returned.
     *
     * Example:
     * ```php
     * $array = [
     *     'users' => [
     *         ['name' => 'Alice'],
     *         ['name' => 'Bob'],
     *     ],
     * ];
     * data_get($array, 'users.*.name'); // ['Alice', 'Bob']
     * data_get($array, 'users.0.name'); // 'Alice'
     * data_get($array, 'users.2.name', 'Unknown'); // 'Unknown'
     * ```
     *
     * @template TValue
     * @template TGetDefault
     * @param array<array-key, TValue> $array   The array to retrieve values from
     * @param array-key                $key     The key string using dot notation
     * @param TGetDefault              $default Default value if the key does not exist
     * @return TGetDefault|array<array-key, TValue>|null Returns the value, multiple values, or the default
     */
    function data_get(array $array, int|string $key, $default = null): mixed
    {
        $segments = explode('.', (string) $key);
        foreach ($segments as $segment) {
            if (array_key_exists($segment, $array)) {
                $array = $array[$segment];
            } elseif ('*' === $segment) {
                $values = [];
                foreach ($array as $item) {
                    /** @phpstan-ignore-next-line */
                    $value = data_get($item, implode('.', array_slice($segments, 1)));
                    if (null !== $value) {
                        $values[] = $value;
                    }
                }

                return count($values) > 0 ? $values : $default;
            } else {
                return $default;
            }
        }

        return $array;
    }
}
