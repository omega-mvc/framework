<?php

declare(strict_types=1);

namespace Tests\Time;

use DateInvalidTimeZoneException;
use DateMalformedStringException;
use Omega\Time\Now;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversFunction;
use Tests\TestCase;

use function date_default_timezone_get;
use function Omega\Time\now;
use function strtotime;

#[CoversClass(Now::class)]
final class HelperFunctionTest extends TestCase
{
    public function testReturnsANowInstanceWithCurrentTimeByDefault(): void
    {
        $instance = now();

        $this->assertSame(Now::class, $instance::class);
    }
    public function testUsesCurrentDateAndDefaultTimezone(): void
    {
        $instance = now('now');

        $this->assertEquals(date_default_timezone_get(), $instance->getTimeZone());

        $timestamp = $instance->getTimestamp();
        $this->assertGreaterThanOrEqual((int) strtotime('-1 minute'), $timestamp);
        $this->assertLessThanOrEqual((int) strtotime('+1 minute'), $timestamp);
    }
    public function testAcceptsACustomDate(): void
    {
        $instance = now('2023-01-29');

        $this->assertSame(2023, $instance->getYear());
        $this->assertSame(1, $instance->getMonth());
        $this->assertSame(29, $instance->getDay());
    }
    public function testAcceptsACustomDateAndTimezone(): void
    {
        $instance = now('2023-01-29', 'Europe/Rome');

        $this->assertSame(2023, $instance->getYear());
        $this->assertSame('Europe/Rome', $instance->getTimeZone());
    }
    public function testThrowsDateMalformedStringExceptionWithAnInvalidDate(): void
    {
        $this->expectException(DateMalformedStringException::class);

        now('not-a-date');
    }
    public function testThrowsDateInvalidTimeZoneExceptionWithAnInvalidTimezone(): void
    {
        $this->expectException(DateInvalidTimeZoneException::class);

        now('2023-01-29', 'Not/AZone');
    }
}
