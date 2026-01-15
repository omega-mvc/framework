<?php

/**
 * Part of Omega - Text Package.
 *
 * @link      https://omega-mvc.github.io
 * @author    Adriano Giovannini <agisoftt@gmail.com>
 * @copyright Copyright (c) 2025 - 2026 Adriano Giovannini (https://omega-mvc.github.io)
 * @license   https://www.gnu.org/licenses/gpl-3.0-standalone.html     GPL V3.0+
 * @version   2.0.0
 */

declare(strict_types=1);

namespace Omega\Text;

/**
 * Text Helpers.
 *
 * Provides convenient helper functions to create and manipulate Text objects
 * without needing to instantiate the Text class manually. These functions
 * allow for a fluent and readable interface for working with strings.
 *
 * @category  Omega
 * @package   Text
 * @link      https://omega-mvc.github.io
 * @author    Adriano Giovannini <agisoftt@gmail.com>
 * @copyright Copyright (c) 2025 - 2026 Adriano Giovannini (https://omega-mvc.github.io)
 * @license   https://www.gnu.org/licenses/gpl-3.0-standalone.html     GPL V3.0+
 * @version   2.0.0
 */

if (!function_exists('string')) {
    /**
     * Create a new Text instance from a plain string.
     *
     * This helper allows for a concise and fluent interface when working with
     * the Text class.
     *
     * @param string $text The input string to wrap in a Text object.
     * @return Text A new Text instance initialized with the provided string.
     */
    function string(string $text): Text
    {
        return new Text($text);
    }
}

if (!function_exists('text')) {
    /**
     * Create a new Text instance from a plain string.
     *
     * This helper is an alias of `string()` for semantic flexibility and
     * readability.
     *
     * @param string $text The input string to wrap in a Text object.
     * @return Text A new Text instance initialized with the provided string.
     */
    function text(string $text): Text
    {
        return new Text($text);
    }
}
