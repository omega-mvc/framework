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

declare(strict_types=1);

namespace Tests\Time\Traits;

use Omega\Time\Now;
use Omega\Time\Traits\DateTimeFormatTrait;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversTrait;
use Tests\TestCase;

#[CoversClass(Now::class)]
#[CoversTrait(DateTimeFormatTrait::class)]
final class DateTimeFormatTraitTest extends TestCase
{
    public function testFormatsTimeInAtomFormat(): void
    {
        $now = new Now('2026-03-02 15:30:45', 'UTC');

        $this->assertSame('2026-03-02T15:30:45+00:00', $now->formatATOM());
    }

    public function testFormatsTimeInCookieFormatWithStandardTime(): void
    {
        $now = new Now('02-03-2026', 'UTC');

        $this->assertEquals('Monday, 02-Mar-2026 00:00:00 UTC', $now->formatCOOKIE());
    }

    public function testFormatsTimeInRfc822Format(): void
    {
        $now = new Now('2026-03-02 15:30:45', 'UTC');

        $this->assertSame('Mon, 02 Mar 26 15:30:45 +0000', $now->formatRFC822());
    }

    public function testFormatsTimeInRfc850Format(): void
    {
        $now = new Now('2026-03-02 15:30:45', 'UTC');

        $this->assertSame('Monday, 02-Mar-26 15:30:45 UTC', $now->formatRFC850());
    }

    public function testFormatsTimeInRfc1036Format(): void
    {
        $now = new Now('2026-03-02 15:30:45', 'UTC');

        $this->assertSame('Mon, 02 Mar 26 15:30:45 +0000', $now->formatRFC1036());
    }

    public function testFormatsTimeInRfc1123Format(): void
    {
        $now = new Now('2026-03-02 15:30:45', 'UTC');

        $this->assertSame('Mon, 02 Mar 2026 15:30:45 +0000', $now->formatRFC1123());
    }

    public function testFormatsTimeInRfc7231Format(): void
    {
        $now = new Now('2026-03-02 15:30:45', 'UTC');

        $this->assertSame('Mon, 02 Mar 2026 15:30:45 GMT', $now->formatRFC7231());
    }

    public function testFormatsTimeInRfc2822Format(): void
    {
        $now = new Now('2026-03-02 15:30:45', 'UTC');

        $this->assertSame('Mon, 02 Mar 2026 15:30:45 +0000', $now->formatRFC2822());
    }

    public function testFormatsTimeInRfc3339Format(): void
    {
        $now = new Now('2026-03-02 15:30:45', 'UTC');

        $this->assertSame('2026-03-02T15:30:45+00:00', $now->formatRFC3339());
    }

    public function testFormatsTimeInExtendedRfc3339FormatWithMilliseconds(): void
    {
        $now = new Now('2026-03-02 15:30:45', 'UTC');

        $this->assertSame('2026-03-02T15:30:45.000+00:00', $now->formatRFC3339(true));
    }

    public function testFormatsTimeInRssFormat(): void
    {
        $now = new Now('2026-03-02 15:30:45', 'UTC');

        $this->assertSame('Mon, 02 Mar 2026 15:30:45 +0000', $now->formatRSS());
    }

    public function testFormatsTimeInW3cFormat(): void
    {
        $now = new Now('2026-03-02 15:30:45', 'UTC');

        $this->assertSame('2026-03-02T15:30:45+00:00', $now->formatW3C());
    }
}
