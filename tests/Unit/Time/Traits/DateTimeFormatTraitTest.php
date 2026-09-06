<?php

/**
 * Part of Omega - Tests\Time Package.
 *
 * @link      https://omega-mvc.github.io
 * @author    Adriano Giovannini <agisoftt@gmail.com>
 * @copyright Copyright (c) 2025 - 2026 Adriano Giovannini (https://omega-mvc.github.io)
 * @license   https://www.gnu.org/licenses/gpl-3.0-standalone.html     GPL V3.0+
 * @version   2.0.0
 */

namespace Tests\Time\Traits;

use Omega\Time\Now;
use Omega\Time\Traits\DateTimeFormatTrait;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversTrait;
use PHPUnit\Framework\TestCase;

/**
 * Class DateTimeFormatTraitTest
 *
 * This test class covers the DateTimeFormatTrait, ensuring that
 * all date/time formatting methods produce the expected string
 * outputs according to their respective standards (ATOM, COOKIE,
 * RFC822, RFC850, RFC1036, RFC1123, RFC7231, RFC2822, RFC3339,
 * RSS, W3C).
 *
 * Each method verifies that the trait correctly formats a given
 * date/time string in the specified timezone.
 *
 * @category   Tests
 * @package    Time
 * @subpackage Traits
 * @link       https://omega-mvc.github.io
 * @author     Adriano Giovannini <agisoftt@gmail.com>
 * @copyright  Copyright (c) 2025 - 2026 Adriano Giovannini (https://omega-mvc.github.io)
 * @license    https://www.gnu.org/licenses/gpl-3.0-standalone.html     GPL V3.0+
 * @version    2.0.0
 */
#[CoversClass(Now::class)]
#[CoversTrait(DateTimeFormatTrait::class)]
class DateTimeFormatTraitTest extends TestCase
{
    /**
     * Test formatATOM() returns correct ATOM formatted string.
     *
     * @return void
     */
    public function testFormatATOM(): void
    {
        $now = new Now('2026-03-02 15:30:45', 'UTC');

        $this->assertSame('2026-03-02T15:30:45+00:00', $now->formatATOM());
    }

    /**
     * Test it can return formatted time with standard time.
     *
     * @return void
     */
    public function testItCanReturnFormatedTimeWithStandardTime(): void
    {
        $now = new Now('02-03-2026', 'UTC');

        $this->assertEquals('Monday, 02-Mar-2026 00:00:00 UTC', $now->formatCOOKIE());
    }

    /**
     * Test formatRFC822() returns correct RFC822 formatted string.
     *
     * @return void
     */
    public function testFormatRFC822(): void
    {
        $now = new Now('2026-03-02 15:30:45', 'UTC');

        $this->assertSame('Mon, 02 Mar 26 15:30:45 +0000', $now->formatRFC822());
    }

    /**
     * Test formatRFC850() returns correct RFC850 formatted string.
     *
     * @return void
     */
    public function testFormatRFC850(): void
    {
        $now = new Now('2026-03-02 15:30:45', 'UTC');

        $this->assertSame('Monday, 02-Mar-26 15:30:45 UTC', $now->formatRFC850());
    }

    /**
     * Test formatRFC1036() returns correct RFC1036 formatted string.
     *
     * @return void
     */
    public function testFormatRFC1036(): void
    {
        $now = new Now('2026-03-02 15:30:45', 'UTC');

        $this->assertSame('Mon, 02 Mar 26 15:30:45 +0000', $now->formatRFC1036());
    }

    /**
     * Test formatRFC1123() returns correct RFC1123 formatted string.
     *
     * @return void
     */
    public function testFormatRFC1123(): void
    {
        $now = new Now('2026-03-02 15:30:45', 'UTC');

        $this->assertSame('Mon, 02 Mar 2026 15:30:45 +0000', $now->formatRFC1123());
    }

    /**
     * Test formatting the date/time in RFC7231 (HTTP date) format.
     *
     * Example: Mon, 02 Mar 2026 15:30:45 GMT
     */
    public function testFormatRFC7231(): void
    {
        $now = new Now('2026-03-02 15:30:45', 'UTC');

        $this->assertSame('Mon, 02 Mar 2026 15:30:45 GMT', $now->formatRFC7231());
    }

    /**
     * Test formatting the date/time in RFC2822 format.
     *
     * Example: Mon, 02 Mar 2026 15:30:45 +0000
     */
    public function testFormatRFC2822(): void
    {
        $now = new Now('2026-03-02 15:30:45', 'UTC');

        $this->assertSame('Mon, 02 Mar 2026 15:30:45 +0000', $now->formatRFC2822());
    }

    /**
     * Test formatting date/time in standard RFC3339 format.
     *
     * Example: 2026-03-02T15:30:45+00:00
     *
     * @return void
     */
    public function testFormatRFC3339(): void
    {
        $now = new Now('2026-03-02 15:30:45', 'UTC');

        $this->assertSame('2026-03-02T15:30:45+00:00', $now->formatRFC3339());
    }

    /**
     * Test formatting date/time in extended RFC3339 format with milliseconds.
     *
     * Example: 2026-03-02T15:30:45.000+00:00
     *
     * @return void
     */
    public function testFormatRFC3339Extended(): void
    {
        $now = new Now('2026-03-02 15:30:45', 'UTC');

        $this->assertSame('2026-03-02T15:30:45.000+00:00', $now->formatRFC3339(true));
    }

    /**
     * Test formatting date/time in RSS format.
     *
     * Example: Mon, 02 Mar 2026 15:30:45 +0000
     *
     * @return void
     */
    public function testFormatRSS(): void
    {
        $now = new Now('2026-03-02 15:30:45', 'UTC');

        $this->assertSame('Mon, 02 Mar 2026 15:30:45 +0000', $now->formatRSS());
    }

    /**
     * Test formatting date/time in W3C format.
     *
     * Example: 2026-03-02T15:30:45+00:00
     *
     * @return void
     */
    public function testFormatW3C(): void
    {
        $now = new Now('2026-03-02 15:30:45', 'UTC');

        $this->assertSame('2026-03-02T15:30:45+00:00', $now->formatW3C());
    }
}
