<?php

/**
 * Part of Omega - Time Package.
 *
 * @link      https://omega-mvc.github.io
 * @author    Adriano Giovannini <agisoftt@gmail.com>
 * @copyright Copyright (c) 2025 - 2026 Adriano Giovannini (https://omega-mvc.github.io)
 * @license   https://www.gnu.org/licenses/gpl-3.0-standalone.html     GPL V3.0+
 * @version   2.0.0
 */

declare(strict_types=1);

namespace Omega\Time;

use DateInvalidTimeZoneException;
use DateMalformedStringException;

use function function_exists;

/**
 * Helper functions for Omega Time package.
 *
 * This file provides utility functions to easily access the `Now` class,
 * allowing quick instantiation of time objects with optional date and timezone.
 *
 * Example usage:
 * ```php
 * $now = now(); // current time in default timezone
 * $utcNow = now('now', 'UTC'); // current time in UTC
 * $customDate = now('2023-01-29', 'Europe/Rome'); // specific date in a timezone
 * ```
 *
 * @category  Omega
 * @package   Time
 * @link      https://omega-mvc.github.io
 * @author    Adriano Giovannini <agisoftt@gmail.com>
 * @copyright Copyright (c) 2025 - 2026 Adriano Giovannini (https://omega-mvc.github.io)
 * @license   https://www.gnu.org/licenses/gpl-3.0-standalone.html     GPL V3.0+
 * @version   2.0.0
 */
if (!function_exists('now')) {
    /**
     * Returns a `Now` object representing a specific date and timezone.
     *
     * This function is a convenient shortcut to create instances of the `Now` class.
     * By default, it returns the current time in the system's default timezone.
     *
     * @param string      $date_format The date string to initialize the object (default 'now').
     * @param string|null $time_zone   Optional timezone identifier (e.g., 'UTC', 'Europe/Rome').
     * @return Now An instance of the `Now` class representing the specified date and timezone.
     * @throws DateInvalidTimeZoneException Thrown when a provided timezone is invalid.
     * @throws DateMalformedStringException Thrown when a date string cannot be parsed correctly.
     */
    function now(string $date_format = 'now', ?string $time_zone = null): Now
    {
        return new Now($date_format, $time_zone);
    }
}
