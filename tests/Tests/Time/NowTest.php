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

use DateInvalidTimeZoneException;
use DateMalformedStringException;
use DateTimeImmutable;
use DateTimeZone;
use Omega\Time\Now;
use Omega\Time\Traits\DateTimeFormatTrait;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversFunction;
use PHPUnit\Framework\Attributes\CoversTrait;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

use function date;
use function date_default_timezone_set;
use function strtotime;

/**
 * Class TimeTravelTest
 *
 * Unit tests for the Omega\Time\Now class.
 *
 * This test suite covers:
 *  - Current and custom time initialization.
 *  - Accessing and modifying time components (year, month, day, hour, minute, second)
 *    via methods and magic properties.
 *  - Correct calculation of age, including edge cases:
 *      - Typical birthdays
 *      - Leap year birthdays
 *      - Future birthdays
 *      - Birthdays today
 *      - Birthdays around the current day
 *  - Handling of different time zones.
 *  - Validation of getters and setters for private properties.
 *  - Exception handling when accessing or modifying undefined or non-settable properties.
 *  - Formatting functions for various date representations.
 *
 * Note:
 *  - Some tests rely on a fixed UTC timezone for consistency across environments.
 *  - The `@noinspection` annotations are used to suppress false-positive inspections
 *    by PHPStorm when testing magic properties.
 *
 * @category  Tests
 * @package   Time
 * @link      https://omega-mvc.github.io
 * @author    Adriano Giovannini <agisoftt@gmail.com>
 * @copyright Copyright (c) 2025 - 2026 Adriano Giovannini (https://omega-mvc.github.io)
 * @license   https://www.gnu.org/licenses/gpl-3.0-standalone.html     GPL V3.0+
 * @version   2.0.0
 */
#[CoversClass(Now::class)]
#[CoversFunction('Omega\Time\now')]
#[CoversTrait(DateTimeFormatTrait::class)]
final class NowTest extends TestCase
{
    /**
     * Test it same with current time.
     *
     * @return void
     * @throws DateInvalidTimeZoneException Thrown when a provided timezone is invalid.
     * @throws DateMalformedStringException Thrown when a date string cannot be parsed correctly.
     */
    public function testItSameWithCurrentTime(): void
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

    /**
     * Test it same with custom time.
     *
     * @return void
     * @throws DateMalformedStringException Thrown when a date string cannot be parsed correctly.
     */
    public function testItSameWithCustomTime(): void
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

        $this->assertEquals((int) date('Y', $time), $now->getYear(), 'Year must match');
        $this->assertEquals((int) date('n', $time), $now->getMonth(), 'Month must match');
        $this->assertEquals((int) date('d', $time), $now->getDay(), 'Day must match');
        $this->assertEquals(date('D', $time), $now->getShortDay(), 'Short day must match');
        $this->assertEquals((int) date('H', $time), $now->getHour(), 'Hour must match');
        $this->assertEquals((int) date('i', $time), $now->getMinute(), 'Minute must match');
        $this->assertEquals((int) date('s', $time), $now->getSecond(), 'Second must match');
        $this->assertEquals(date('l', $time), $now->getDayName(), 'Full day name must match');
        $this->assertEquals(date('F', $time), $now->getMonthName(), 'Month name must match');

        $this->assertTrue($now->isJul(), 'Month should be July');
        $this->assertTrue($now->isSaturday(), 'Day should be Saturday');
    }

    /**
     * Test it calculates age correctly for typical birthday.
     *
     * @return void
     * @throws DateInvalidTimeZoneException Thrown when a provided timezone is invalid.
     * @throws DateMalformedStringException Thrown when a date string cannot be parsed correctly.
     */
    public function testItCalculatesAgeCorrectlyForTypicalBirthday(): void
    {
        $today = new DateTimeImmutable('2026-03-01', new DateTimeZone('UTC'));

        $birthday = '1990-01-01';
        $now = new Now($birthday, 'UTC');

        $expectedAge = $today->diff(new DateTimeImmutable($birthday, new DateTimeZone('UTC')))->y;

        $actualAge = $now->getAge();

        $this->assertSame(
            $expectedAge,
            $actualAge,
            'The age must equal'
        );
    }

    /**
     * Test it handles leap year birthday correctly.
     *
     * @return void
     * @throws DateInvalidTimeZoneException Thrown when a provided timezone is invalid.
     * @throws DateMalformedStringException Thrown when a date string cannot be parsed correctly.
     */
    public function testItHandlesLeapYearBirthdayCorrectly(): void
    {
        $today = new DateTimeImmutable('2026-03-01', new DateTimeZone('UTC'));

        $birthday = '2000-02-29';
        $now = new Now($birthday, 'UTC');

        $birthDate = new DateTimeImmutable($birthday, new DateTimeZone('UTC'));
        $expectedAge = $birthDate->diff($today)->y;

        // usa il getter pubblico invece della proprietà
        $actualAge = $now->getAge();

        $this->assertSame(
            $expectedAge,
            $actualAge,
            'The age must equal for leap year birthday'
        );
    }

    /**
     * Test it handles future birthdate correctly.
     *
     * @return void
     */
    public function testItHandlesFutureBirthdateCorrectly(): void
    {
        $future = new Now('+1 day');
        $this->assertSame(
            0,
            $future->getAge(),
            'Age must be 0 for a future birthdate'
        );
    }

    /**
     * Test it calculate age as zero for today birthdate.
     *
     * @return void
     * @throws DateInvalidTimeZoneException Thrown when a provided timezone is invalid.
     * @throws DateMalformedStringException Thrown when a date string cannot be parsed correctly.
     */
    public function testItCalculatesAgeAsZeroForTodayBirthdate(): void
    {
        $todayUtc = new DateTimeImmutable('now', new DateTimeZone('UTC'));

        $now = new Now($todayUtc->format('Y-m-d H:i:s'), 'UTC');

        $this->assertSame(
            0,
            $now->getAge(),
            'the age must be 0 for today\'s birthdate in UTC'
        );
    }

    /**
     * Test it handles edge cases around birthday correctly.
     *
     * @return void
     * @throws DateMalformedStringException Thrown when a date string cannot be parsed correctly.
     * @throws DateInvalidTimeZoneException Thrown when a provided timezone is invalid.
     */
    public function testItHandlesEdgeCasesAroundBirthdayCorrectly(): void
    {
        $yesterday = new DateTimeImmutable('yesterday', new DateTimeZone('UTC'));
        $nowBeforeBirthday = new Now($yesterday->format('Y-m-d'), 'UTC');
        $birthDateBefore   = new DateTimeImmutable($yesterday->format('Y-m-d'));
        $expectedAgeBefore = $birthDateBefore->diff(new DateTimeImmutable('now'))->y;

        $this->assertSame(
            $expectedAgeBefore,
            $nowBeforeBirthday->getAge(),
            'the age must be correct just before the birthday'
        );

        $tomorrow = new DateTimeImmutable('tomorrow', new DateTimeZone('UTC'));
        $nowAfterBirthday = new Now($tomorrow->format('Y-m-d'), 'UTC');
        $birthDateAfter   = new DateTimeImmutable($tomorrow->format('Y-m-d'));
        $expectedAgeAfter = $birthDateAfter->diff(new DateTimeImmutable('now'))->y;

        $this->assertSame(
            $expectedAgeAfter,
            $nowAfterBirthday->getAge(),
            'the age must be correct just after the birthday'
        );
    }

    /**
     * Test it handle different time zones correctly.
     *
     * @return void
     * @throws DateMalformedStringException Thrown when a date string cannot be parsed correctly.
     */
    public function testItHandlesDifferentTimeZonesCorrectly(): void
    {
        $utcZone = new DateTimeZone('UTC');
        $romeZone = new DateTimeZone('Europe/Rome');

        $nowUTC = new Now('2000-01-01', 'UTC');
        $nowRM  = new Now('2000-01-01', 'Europe/Rome');

        $birthDateUTC = new DateTimeImmutable('2000-01-01', $utcZone);
        $birthDateRM  = new DateTimeImmutable('2000-01-01', $romeZone);

        $expectedAgeUTC = $birthDateUTC->diff(new DateTimeImmutable('now', $utcZone))->y;
        $expectedAgeRM  = $birthDateRM->diff(new DateTimeImmutable('now', $romeZone))->y;

        $this->assertSame(
            $expectedAgeUTC,
            $nowUTC->getAge(),
            'the age must be correct in UTC'
        );

        $this->assertSame(
            $expectedAgeRM,
            $nowRM->getAge(),
            'the age must be correct in Europe/Rome'
        );
    }

    /**
     * Test it can get from private property.
     *
     * @return void
     * @throws DateMalformedStringException Thrown when a date string cannot be parsed correctly.
     */
    public function testItCanGetFromPrivateProperty(): void
    {
        $now = new Now();
        $nowUpdated = $now->setDay(12);

        $this->assertEquals(12, $nowUpdated->getDay());
    }

    /**
     * Test it can set from property.
     *
     * @return void
     * @throws DateMalformedStringException Thrown when a date string cannot be parsed correctly.
     */
    public function testItCanSetMonthViaFluentMethod(): void
    {
        $now = new Now();
        $updatedNow = $now->setMonth(7);

        $this->assertEquals(7, $updatedNow->getMonth());
    }

    /**
     * Test it can set year via fluent method.
     *
     * @return void
     * @throws DateMalformedStringException Thrown when a date string cannot be parsed correctly.
     */
    public function testItCanSetYearViaFluentMethod(): void
    {
        $now = new Now();
        $updatedNow = $now->setYear(2025);

        $this->assertEquals(2025, $updatedNow->getYear());
    }

    /**
     * Test it can use private property using setter and getter.
     *
     * @return void
     * @throws DateMalformedStringException Thrown when a date string cannot be parsed correctly.
     */
    public function testItCanUseSettersAndGetters(): void
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

    /**
     * Test it can return formatted time.
     *
     * @return void
     */
    public function testItCanReturnFormatedTime(): void
    {
        $now = new Now('29-01-2023');

        $this->assertEquals('2023-01-29', $now->format('Y-m-d'));
    }

    /**
     * Provides numeric months and the corresponding month helper method that should return true.
     *
     * Each entry is an array where:
     *   - [0] => int $month The numeric month (1-12).
     *   - [1] => string $expectedTrueMethod The name of the month helper method expected to return true (e.g., 'isJan').
     *
     * @return array<int, array{int, string}>
     */
    public static function monthProvider(): array
    {
        return [
            [1, 'isJan'],
            [2, 'isFeb'],
            [3, 'isMar'],
            [4, 'isApr'],
            [5, 'isMay'],
            [6, 'isJun'],
            [7, 'isJul'],
            [8, 'isAug'],
            [9, 'isSep'],
            [10, 'isOct'],
            [11, 'isNov'],
            [12, 'isDec'],
        ];
    }

    /**
     * Test month helpers.
     *
     * This test checks that only the method corresponding to the given month
     * returns true, while all other month-check methods return false.
     *
     * @param int $month The numeric month (1-12) to test.
     * @param string $expectedTrueMethod The name of the helper method that should return true for this month (e.g., 'isJan').
     * @return void
     * @throws DateMalformedStringException Thrown when a date string cannot be parsed correctly.
     * @throws DateInvalidTimeZoneException Thrown when a provided timezone is invalid.
     */
    #[DataProvider('monthProvider')]
    public function testMonthHelpers(int $month, string $expectedTrueMethod): void
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
                $this->assertTrue($now->$method(), "$method should be true");
            } else {
                $this->assertFalse($now->$method(), "$method should be false");
            }
        }
    }

    /**
     * Provides dates and the corresponding weekday helper method that should return true.
     *
     * Each entry is an array where:
     *   - [0] => string $date The date in 'Y-m-d' format.
     *   - [1] => string $expectedTrueMethod The name of the weekday helper method expected to return true (e.g., 'isMonday').
     *
     * @return array<int, array{string, string}>
     */
    public static function weekdayProvider(): array
    {
        return [
            ['2023-01-02', 'isMonday'],
            ['2023-01-03', 'isTuesday'],
            ['2023-01-04', 'isWednesday'],
            ['2023-01-05', 'isThursday'],
            ['2023-01-06', 'isFriday'],
            ['2023-01-07', 'isSaturday'],
            ['2023-01-08', 'isSunday'],
        ];
    }

    /**
     * Test week day helpers.
     *
     * This test verifies that only the method corresponding to the given weekday
     * returns true, while all other weekday-check methods return false.
     *
     * @param string $date The date string to test (format: 'Y-m-d').
     * @param string $expectedTrueMethod The name of the helper method that should return true for this weekday (e.g., 'isMonday').
     * @return void
     * @throws DateMalformedStringException Thrown when a date string cannot be parsed correctly.
     * @throws DateInvalidTimeZoneException Thrown when a provided timezone is invalid.
     */
    #[DataProvider('weekdayProvider')]
    public function testWeekdayHelpers(string $date, string $expectedTrueMethod): void
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
                $this->assertTrue($now->$method(), "$method should be true");
            } else {
                $this->assertFalse($now->$method(), "$method should be false");
            }
        }
    }

    /**
     * Test to string coverage.
     *
     * @return void
     */
    public function testToStringCoverage(): void
    {
        $now = new Now('2023-03-01 15:30:45', 'UTC');

        $result = $now->__toString();

        $this->assertSame('2023-03-01T15:30:45', $result);
    }

    /**
     * Test it converts to string direct call
     *
     * @return void
     */
    public function testItConvertsToStringDirectCall(): void
    {
        $now = new Now('2023-03-01 15:30:45', 'UTC');
        $this->assertSame('2023-03-01T15:30:45', $now->__toString());
    }

    /**
     * Provides test data for "next" helpers.
     *
     * Each data set contains:
     * - unit: the time unit to test (year, month, day, hour, minute)
     * - modify: a string modification to create the target date
     * - expected: whether the now() instance should match the modified date
     *
     * @return array<int, array{0:string,1:string,2:bool}> Test cases for next helpers
     */
    public static function nextProvider(): array
    {
        return [
            ['year',   '+1 year',   true],
            ['month',  'first day of next month', true],
            ['day',    'tomorrow',  true],
            ['hour',   '+1 hour',   true],
            ['minute', '+1 minute', true],
        ];
    }

    /**
     * Test the "next" helper methods.
     *
     * @param string $unit     The time unit to test (year, month, day, hour, minute)
     * @param string $modify   The modification string to create the target date
     * @param bool   $expected Whether the helper should return true
     * @return void
     * @throws DateMalformedStringException Thrown when a date string cannot be parsed correctly.
     * @throws DateInvalidTimeZoneException Thrown when a provided timezone is invalid.
     */
    #[DataProvider('nextProvider')]
    public function testNextHelpers(string $unit, string $modify, bool $expected): void
    {
        $now = new Now($modify);

        $method = "isNext" . ucfirst($unit);

        $this->assertSame(
            $expected,
            $now->$method(),
            "Failed the test for $unit using modification '$modify'"
        );
    }

    /**
     * Provides test data for "last" helpers.
     *
     * Each data set contains:
     * - unit: the time unit to test (year, month, day, hour, minute)
     * - modify: a string modification to create the target date
     * - expected: whether the now() instance should match the modified date
     *
     * @return array<int, array{0:string,1:string,2:bool}> Test cases for last helpers
     */
    public static function lastProvider(): array
    {
        return [
            ['year',   '-1 year',                  true],
            ['month',  'first day of last month',  true],
            ['day',    'yesterday',                true],
            ['hour',   '-1 hour',                  true],
            ['minute', '-1 minute',                true],
        ];
    }

    /**
     * Test the "last" helper methods.
     *
     * @param string $unit     The time unit to test (year, month, day, hour, minute)
     * @param string $modify   The modification string to create the target date
     * @param bool   $expected Whether the helper should return true
     * @return void
     * @throws DateMalformedStringException Thrown when a date string cannot be parsed correctly.
     * @throws DateInvalidTimeZoneException Thrown when a provided timezone is invalid.
     */
    #[DataProvider('lastProvider')]
    public function testLastHelpers(string $unit, string $modify, bool $expected): void
    {
        $now = new Now($modify);

        $method = "isLast" . ucfirst($unit);

        $this->assertSame(
            $expected,
            $now->$method(),
            "Failed the test for $unit using modification '$modify'"
        );
    }
}
