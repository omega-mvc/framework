<?php

/**
 * Part of Omega - Config Package.
 *
 * @link      https://omega-mvc.github.io
 * @author    Adriano Giovannini <agisoftt@gmail.com>
 * @copyright Copyright (c) 2025 - 2026 Adriano Giovannini (https://omega-mvc.github.io)
 * @license   https://www.gnu.org/licenses/gpl-3.0-standalone.html     GPL V3.0+
 * @version   2.0.0
 */

declare(strict_types=1);

namespace Omega\Config;

use Omega\Support\Enum\AbstractEnum;

/**
 * Defines merge strategies for configuration data.
 *
 * This enum-like class extends `AbstractEnum` and provides constants that determine
 * how conflicting indexed arrays should be merged within the configuration system.
 * Available strategies:
 *
 * - `REPLACE_INDEXED`: Replaces the existing indexed array with the new one.
 *
 * - `MERGE_INDEXED`: Merges the new indexed array into the existing one.
 * - `MERGE_ADD_NEW`: Merges the new indexed array but only adds new elements, preserving existing ones.
 *
 * @category  Omega
 * @package   Config
 * @link      https://omega-mvc.github.io
 * @author    Adriano Giovannini <agisoftt@gmail.com>
 * @copyright Copyright (c) 2025 - 2026 Adriano Giovannini (https://omega-mvc.github.io)
 * @license   https://www.gnu.org/licenses/gpl-3.0-standalone.html     GPL V3.0+
 * @version   2.0.0
 */
final class MergeStrategy extends AbstractEnum
{
    /**
     * Replaces conflicting indexed arrays completely.
     *
     * Example:
     * ```php
     * $configA = ['items' => [1, 2, 3]];
     * $configB = ['items' => [4, 5]];
     * // Using REPLACE_INDEXED results in:
     * ['items' => [4, 5]];
     * ```
     */
    public const int REPLACE_INDEXED = 0;

    /**
     * Merges conflicting indexed arrays by appending elements.
     *
     * Example:
     * ```php
     * $configA = ['items' => [1, 2, 3]];
     * $configB = ['items' => [4, 5]];
     * // Using MERGE_INDEXED results in:
     * ['items' => [1, 2, 3, 4, 5]];
     * ```
     */
    public const int MERGE_INDEXED = 1;

    /**
     * Merges arrays while preserving existing indexed keys, adding only new elements.
     *
     * Example:
     * ```php
     * $configA = ['items' => [1 => 'a', 2 => 'b']];
     * $configB = ['items' => [2 => 'x', 3 => 'c']];
     * // Using MERGE_ADD_NEW results in:
     * ['items' => [1 => 'a', 2 => 'b', 3 => 'c']];
     * ```
     */
    public const int MERGE_ADD_NEW = 2;
}
