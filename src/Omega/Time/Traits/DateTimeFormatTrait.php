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

namespace Omega\Time\Traits;

use DateTimeInterface;

/**
 * Trait DateTimeFormatTrait
 *
 * Provides convenient methods to format a DateTime-like object into
 * standard PHP DateTimeInterface formats (ATOM, RFC822, RFC3339, etc.).
 *
 * Intended to be used by classes implementing a `format(string $format): string` method,
 * typically wrappers around DateTime or custom time objects.
 *
 * Example usage:
 * ```php
 * $now = new Now();
 * echo $now->formatATOM(); // 2025-10-27T12:34:56+00:00
 * ```
 *
 * @category   Omega
 * @package    Time
 * @subpackage Traits
 * @link       https://omega-mvc.github.io
 * @author     Adriano Giovannini <agisoftt@gmail.com>
 * @copyright  Copyright (c) 2025 - 2026 Adriano Giovannini (https://omega-mvc.github.io)
 * @license    https://www.gnu.org/licenses/gpl-3.0-standalone.html     GPL V3.0+
 * @version    2.0.0
 */
trait DateTimeFormatTrait
{
    /**
     * Format the date/time in ATOM format.
     *
     * Example: 2025-10-27T12:34:56+00:00
     *
     * @return string Formatted date/time.
     */
    public function formatATOM(): string
    {
        return $this->format(DateTimeInterface::ATOM);
    }

    /**
     * Format the date/time in COOKIE format.
     *
     * Example: Monday, 27-Oct-2025 12:34:56 UTC
     *
     * @return string Formatted date/time.
     */
    public function formatCOOKIE(): string
    {
        return $this->format(DateTimeInterface::COOKIE);
    }

    /**
     * Format the date/time in RFC822 format.
     *
     * Example: Mon, 27 Oct 25 12:34:56 +0000
     *
     * @return string Formatted date/time.
     */
    public function formatRFC822(): string
    {
        return $this->format(DateTimeInterface::RFC822);
    }

    /**
     * Format the date/time in RFC850 format.
     *
     * Example: Monday, 27-Oct-25 12:34:56 UTC
     *
     * @return string Formatted date/time.
     */
    public function formatRFC850(): string
    {
        return $this->format(DateTimeInterface::RFC822);
    }

    /**
     * Format the date/time in RFC1036 format.
     *
     * Example: Mon, 27 Oct 25 12:34:56 +0000
     *
     * @return string Formatted date/time.
     */
    public function formatRFC1036(): string
    {
        return $this->format(DateTimeInterface::RFC822);
    }

    /**
     * Format the date/time in RFC1123 format.
     *
     * Example: Mon, 27 Oct 2025 12:34:56 +0000
     *
     * @return string Formatted date/time.
     */
    public function formatRFC1123(): string
    {
        return $this->format(DateTimeInterface::RFC1123);
    }

    /**
     * Format the date/time in RFC7231 format (HTTP date).
     *
     * Example: Mon, 27 Oct 2025 12:34:56 GMT
     *
     * @return string Formatted date/time.
     */
    public function formatRFC7231(): string
    {
        return $this->format(DateTimeInterface::RFC7231);
    }

    /**
     * Format the date/time in RFC2822 format.
     *
     * Example: Mon, 27 Oct 2025 12:34:56 +0000
     *
     * @return string Formatted date/time.
     */
    public function formatRFC2822(): string
    {
        return $this->format(DateTimeInterface::RFC2822);
    }

    /**
     * Format the date/time in RFC3339 format.
     *
     * By default, uses the standard RFC3339 format (Y-m-d\TH:i:sP).
     * If $expanded is true, uses RFC3339_EXTENDED (Y-m-d\TH:i:s.vP) with milliseconds.
     *
     * @param bool $expanded Use extended format with milliseconds.
     * @return string Formatted date/time.
     */
    public function formatRFC3339(bool $expanded = false): string
    {
        return $this->format($expanded ? DateTimeInterface::RFC3339_EXTENDED : DateTimeInterface::RFC3339);
    }

    /**
     * Format the date/time in RSS format.
     *
     * Example: Mon, 27 Oct 2025 12:34:56 +0000
     *
     * @return string Formatted date/time.
     */
    public function formatRSS(): string
    {
        return $this->format(DateTimeInterface::RSS);
    }

    /**
     * Format the date/time in W3C format.
     *
     * Example: 2025-10-27T12:34:56+00:00
     *
     * @return string Formatted date/time.
     */
    public function formatW3C(): string
    {
        return $this->format(DateTimeInterface::W3C);
    }
}
