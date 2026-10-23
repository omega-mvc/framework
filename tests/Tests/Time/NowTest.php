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

/** @noinspection PhpConditionAlreadyCheckedInspection */

declare(strict_types=1);

namespace Tests\Time;

use DateTimeImmutable;
use DateTimeZone;
use Omega\Time\Now;
use Omega\Time\Traits\DateTimeFormatTrait;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversTrait;
use PHPUnit\Framework\Attributes\TestWith;
use Tests\TestCase;

use function date;
use function date_default_timezone_set;
use function sprintf;
use function strtotime;
use function ucfirst;

#[CoversClass(Now::class)]
#[CoversTrait(DateTimeFormatTrait::class)]
final class NowTest extends TestCase
{
    public function testIsSameWithCurrentTime(): void
    {
        $tz = new DateTimeZone(date_default_timezone_get());
        $timestamp = new DateTimeImmutable('now', $tz)->getTimestamp();

        $now = new Now('now', date_default_timezone_get());

        $this->assertSame($timestamp, $now->getTimestamp());
        $this->assertSame((int) date('Y', $timestamp), $now->getYear());
        $this->assertSame((int) date('n', $timestamp), $now->getMonth());
        $this->assertSame((int) date('d', $timestamp), $now->getDay());

        $this->assertSame(date('D', $timestamp), $now->getShortDay());
        $this->assertSame((int) date('H', $timestamp), $now->getHour());
        $this->assertSame((int) date('i', $timestamp), $now->getMinute());
        $this->assertSame((int) date('s', $timestamp), $now->getSecond());

        $this->assertSame(date('l', $timestamp), $now->getDayName());
        $this->assertSame(date('F', $timestamp), $now->getMonthName());

        $this->assertSame((int) date('N', $timestamp), $now->getDayOfWeek());
    }

    public function testIsSameWithCustomTime(): void
    {
        date_default_timezone_set('UTC');

        $time = strtotime('2021-07-03 19:52:39');
        $now = new Now();
        $now = $now->setYear(2021)
            ->setMonth(7)
            ->setDay(3)
            ->setHour(19)
            ->setMinute(52)
            ->setSecond(39);

        $this->assertEquals((int) date('Y', $time), $now->getYear());
        $this->assertEquals((int) date('n', $time), $now->getMonth());
        $this->assertEquals((int) date('d', $time), $now->getDay());
        $this->assertEquals(date('D', $time), $now->getShortDay());
        $this->assertEquals((int) date('H', $time), $now->getHour());
        $this->assertEquals((int) date('i', $time), $now->getMinute());
        $this->assertEquals((int) date('s', $time), $now->getSecond());
        $this->assertEquals(date('l', $time), $now->getDayName());
        $this->assertEquals(date('F', $time), $now->getMonthName());

        $this->assertTrue($now->isJul());
        $this->assertTrue($now->isSaturday());
    }

    public function testCalculatesAgeCorrectlyForTypicalBirthday(): void
    {
        $today = new DateTimeImmutable('2026-03-01', new DateTimeZone('UTC'));

        $birthday = '1990-01-01';
        $now = new Now($birthday, 'UTC');

        $expectedAge = $today->diff(new DateTimeImmutable($birthday, new DateTimeZone('UTC')))->y;

        $this->assertSame($expectedAge, $now->getAge());
    }

    public function testHandlesLeapYearBirthdayCorrectly(): void
    {
        $today = new DateTimeImmutable('2026-03-01', new DateTimeZone('UTC'));

        $birthday = '2000-02-29';
        $now = new Now($birthday, 'UTC');

        $birthDate = new DateTimeImmutable($birthday, new DateTimeZone('UTC'));
        $expectedAge = $birthDate->diff($today)->y;

        $this->assertSame($expectedAge, $now->getAge());
    }

    public function testHandlesFutureBirthdateCorrectly(): void
    {
        $future = new Now('+1 day');

        $this->assertSame(0, $future->getAge());
    }

    public function testCalculatesAgeAsZeroForTodayBirthdate(): void
    {
        $todayUtc = new DateTimeImmutable('now', new DateTimeZone('UTC'));

        $now = new Now($todayUtc->format('Y-m-d H:i:s'), 'UTC');

        $this->assertSame(0, $now->getAge());
    }

    public function testHandlesEdgeCasesAroundBirthdayCorrectly(): void
    {
        $yesterday = new DateTimeImmutable('yesterday', new DateTimeZone('UTC'));
        $nowBeforeBirthday = new Now($yesterday->format('Y-m-d'), 'UTC');
        $birthDateBefore   = new DateTimeImmutable($yesterday->format('Y-m-d'));
        $expectedAgeBefore = $birthDateBefore->diff(new DateTimeImmutable('now'))->y;

        $this->assertSame($expectedAgeBefore, $nowBeforeBirthday->getAge());

        $tomorrow = new DateTimeImmutable('tomorrow', new DateTimeZone('UTC'));
        $nowAfterBirthday = new Now($tomorrow->format('Y-m-d'), 'UTC');
        $birthDateAfter   = new DateTimeImmutable($tomorrow->format('Y-m-d'));
        $expectedAgeAfter = $birthDateAfter->diff(new DateTimeImmutable('now'))->y;

        $this->assertSame($expectedAgeAfter, $nowAfterBirthday->getAge());
    }

    public function testHandlesDifferentTimeZonesCorrectly(): void
    {
        $utcZone = new DateTimeZone('UTC');
        $romeZone = new DateTimeZone('Europe/Rome');

        $nowUTC = new Now('2000-01-01', 'UTC');
        $nowRM  = new Now('2000-01-01', 'Europe/Rome');

        $birthDateUTC = new DateTimeImmutable('2000-01-01', $utcZone);
        $birthDateRM  = new DateTimeImmutable('2000-01-01', $romeZone);

        $expectedAgeUTC = $birthDateUTC->diff(new DateTimeImmutable('now', $utcZone))->y;
        $expectedAgeRM  = $birthDateRM->diff(new DateTimeImmutable('now', $romeZone))->y;

        $this->assertSame($expectedAgeUTC, $nowUTC->getAge());
        $this->assertSame($expectedAgeRM, $nowRM->getAge());
    }

    public function testCanGetDayFromPrivatePropertyViaSetter(): void
    {
        $now = new Now();
        $nowUpdated = $now->setDay(12);

        $this->assertEquals(12, $nowUpdated->getDay());
    }

    public function testCanSetMonthViaFluentMethod(): void
    {
        $now = new Now();
        $updatedNow = $now->setMonth(7);

        $this->assertEquals(7, $updatedNow->getMonth());
    }

    public function testCanSetYearViaFluentMethod(): void
    {
        $now = new Now();
        $updatedNow = $now->setYear(2025);

        $this->assertEquals(2025, $updatedNow->getYear());
    }

    public function testCanUseSettersAndGetters(): void
    {
        $now = new Now();

        $now = $now->setYear(2022)
            ->setMonth(1)
            ->setDay(11)
            ->setHour(1)
            ->setMinute(27)
            ->setSecond(0);

        $this->assertEquals(2022, $now->getYear());
        $this->assertEquals(1, $now->getMonth());
        $this->assertEquals(11, $now->getDay());
        $this->assertEquals(1, $now->getHour());
        $this->assertEquals(27, $now->getMinute());
        $this->assertEquals(0, $now->getSecond());

        $this->assertEquals('January', $now->getMonthName());
        $this->assertEquals('Tuesday', $now->getDayName());
        $this->assertEquals('Tue', $now->getShortDay());
        $this->assertEquals('UTC', $now->getTimeZone());

        $this->assertLessThan(200, $now->getAge());
        $this->assertGreaterThan(0, $now->getAge());
    }

    public function testCanReturnFormattedTime(): void
    {
        $now = new Now('29-01-2023');

        $this->assertEquals('2023-01-29', $now->format('Y-m-d'));
    }

    #[TestWith([1, 'isJan'])]
    #[TestWith([2, 'isFeb'])]
    #[TestWith([3, 'isMar'])]
    #[TestWith([4, 'isApr'])]
    #[TestWith([5, 'isMay'])]
    #[TestWith([6, 'isJun'])]
    #[TestWith([7, 'isJul'])]
    #[TestWith([8, 'isAug'])]
    #[TestWith([9, 'isSep'])]
    #[TestWith([10, 'isOct'])]
    #[TestWith([11, 'isNov'])]
    #[TestWith([12, 'isDec'])]
    public function testChecksMonthHelpers(int $month, string $expectedTrueMethod): void
    {
        $date = sprintf('2023-%02d-01', $month);
        $now = new Now($date, 'UTC');

        $allMethods = [
            1  => 'isJan',
            2  => 'isFeb',
            3  => 'isMar',
            4  => 'isApr',
            5  => 'isMay',
            6  => 'isJun',
            7  => 'isJul',
            8  => 'isAug',
            9  => 'isSep',
            10 => 'isOct',
            11 => 'isNov',
            12 => 'isDec',
        ];

        foreach ($allMethods as $method) {
            if ($method === $expectedTrueMethod) {
                $this->assertTrue($now->$method());
            } else {
                $this->assertFalse($now->$method());
            }
        }
    }

    #[TestWith(['2023-01-02', 'isMonday'])]
    #[TestWith(['2023-01-03', 'isTuesday'])]
    #[TestWith(['2023-01-04', 'isWednesday'])]
    #[TestWith(['2023-01-05', 'isThursday'])]
    #[TestWith(['2023-01-06', 'isFriday'])]
    #[TestWith(['2023-01-07', 'isSaturday'])]
    #[TestWith(['2023-01-08', 'isSunday'])]
    public function testChecksWeekdayHelpers(string $date, string $expectedTrueMethod): void
    {
        $now = new Now($date, 'UTC');

        $allMethods = [
            'isMonday',
            'isTuesday',
            'isWednesday',
            'isThursday',
            'isFriday',
            'isSaturday',
            'isSunday',
        ];

        foreach ($allMethods as $method) {
            if ($method === $expectedTrueMethod) {
                $this->assertTrue($now->$method());
            } else {
                $this->assertFalse($now->$method());
            }
        }
    }

    public function testConvertsToString(): void
    {
        $now = new Now('2023-03-01 15:30:45', 'UTC');

        $this->assertSame('2023-03-01T15:30:45', $now->__toString());
    }

    public function testConvertsToStringDirectCall(): void
    {
        $now = new Now('2023-03-01 15:30:45', 'UTC');

        $this->assertSame('2023-03-01T15:30:45', $now->__toString());
    }

    #[TestWith(['year', '+1 year', true])]
    #[TestWith(['month', 'first day of next month', true])]
    #[TestWith(['day', 'tomorrow', true])]
    #[TestWith(['hour', '+1 hour', true])]
    #[TestWith(['minute', '+1 minute', true])]
    public function testChecksNextHelpers(string $unit, string $modify, bool $expected): void
    {
        $now = new Now($modify);

        $method = 'isNext' . ucfirst($unit);

        $this->assertSame($expected, $now->$method());
    }

    #[TestWith(['year', '-1 year', true])]
    #[TestWith(['month', 'first day of last month', true])]
    #[TestWith(['day', 'yesterday', true])]
    #[TestWith(['hour', '-1 hour', true])]
    #[TestWith(['minute', '-1 minute', true])]
    public function testChecksLastHelpers(string $unit, string $modify, bool $expected): void
    {
        $now = new Now($modify);

        $method = 'isLast' . ucfirst($unit);

        $this->assertSame($expected, $now->$method());
    }
}
