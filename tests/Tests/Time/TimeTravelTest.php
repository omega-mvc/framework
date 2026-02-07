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

namespace Tests\Time;

use DateInvalidTimeZoneException;
use DateMalformedStringException;
use DateTime;
use Omega\Time\Exceptions\PropertyNotExistException;
use Omega\Time\Exceptions\PropertyNotSettableException;
use Omega\Time\Now;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

use function date;
use function date_default_timezone_set;
use function strtotime;
use function time;

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
#[CoversClass(PropertyNotExistException::class)]
#[CoversClass(PropertyNotSettableException::class)]
final class TimeTravelTest extends TestCase
{
    /**
     * Test it same with current time.
     *
     * @return void
     */
    public function testItSameWithCurrentTime(): void
    {
        $timestamp = time();
        $now       = new Now();

        $this->assertSame($timestamp, $now->timestamp);

        $this->assertSame((int) date('Y', $timestamp), $now->year);
        $this->assertSame((int) date('n', $timestamp), $now->month);
        $this->assertSame((int) date('d', $timestamp), $now->day);

        $this->assertSame(date('D', $timestamp), $now->shortDay);
        $this->assertSame((int) date('H', $timestamp), $now->hour);
        $this->assertSame((int) date('i', $timestamp), $now->minute);
        $this->assertSame((int) date('s', $timestamp), $now->second);

        $this->assertSame(date('l', $timestamp), $now->dayName);
        $this->assertSame(date('F', $timestamp), $now->monthName);
    }

    /**
     * Test it same with custom time.
     *
     * @return void
     */
    public function testItSameWithCustomTime(): void
    {
        $now = new Now();
        date_default_timezone_set('UTC');
        $time = strtotime('2021-07-03 19:52:39');

        $now->year(2021);
        $now->month(7);
        $now->day(3);
        $now->hour(19);
        $now->minute(52);
        $now->second(39);

        $this->assertEquals(
            date('Y', $time),
            $now->year,
            'timestamp must equal'
        );

        $this->assertEquals(
            date('n', $time),
            $now->month,
            'the time must same with this day'
        );

        $this->assertEquals(
            date('d', $time),
            $now->day,
            'the time must same with this day'
        );

        $this->assertEquals(
            date('D', $time),
            $now->shortDay,
            'the time must same with this short day'
        );

        $this->assertEquals(
            date('H', $time),
            $now->hour,
            'the time must same with this hour'
        );

        $this->assertEquals(
            date('i', $time),
            $now->minute,
            'the time must same with this minute'
        );

        $this->assertEquals(
            date('s', $time),
            $now->second,
            'the time must same with this second'
        );

        $this->assertEquals(
            date('l', $time),
            $now->dayName,
            'the time must same with this day name'
        );

        $this->assertEquals(
            date('F', $time),
            $now->monthName,
            'the time must same with this day name'
        );

        $this->assertTrue($now->isJul(), 'month must same');
        $this->assertTrue($now->isSaturday(), 'day must same');
    }

    /**
     * Test it calculates age correctly for typical birthday.
     *
     * @return void
     */
    public function testItCalculatesAgeCorrectlyForTypicalBirthday(): void
    {
        $now         = new Now('01/01/1990');
        $currentYear = (int) date('Y');
        $expectedAge = $currentYear - 1990;
        $this->assertSame(
            $expectedAge,
            $now->age,
            'the age must equal'
        );
    }

    /**
     * Test it handles leap year birthday correctly.
     *
     * @return void
     * @throws DateMalformedStringException Thrown when a date string cannot be parsed correctly.
     */
    public function testItHandlesLeapYearBirthdayCorrectly(): void
    {
        $now             = new Now('02/29/2000');
        $birthDateBefore = new DateTime(date('02/29/2000'));
        $expectedAge     = $birthDateBefore->diff(new DateTime())->y;
        $this->assertSame(
            $expectedAge,
            $now->age,
            'the age must equal'
        );
    }

    /**
     * Test it handles future birthdate correctly.
     *
     * @return void
     */
    public function testItHandlesFutureBirthdateCorrectly(): void
    {
        $now = new Now('next day');
        $this->assertSame(
            0,
            $now->age,
            'the age must be 0 for a future birthdate'
        );
    }

    /**
     * Test it calculate age as zero for today birthdate.
     *
     * @return void
     */
    public function testItCalculatesAgeAsZeroForTodayBirthdate(): void
    {
        $now = new Now('now', 'utc');
        $this->assertSame(
            0,
            $now->age,
            'the age must be 0 for today\'s birthdate'
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
        $nowBeforeBirthday = new Now(date('m/d/Y', strtotime('-1 day')));
        $birthDateBefore   = new DateTime(date('m/d/Y', strtotime('-1 day')));
        $expectedAgeBefore = $birthDateBefore->diff(new DateTime())->y;
        $this->assertSame(
            $expectedAgeBefore,
            $nowBeforeBirthday->age,
            'the age must be correct just before the birthday'
        );

        $nowAfterBirthday = new Now(date('m/d/Y', strtotime('+1 day')));
        $birthDateAfter   = new DateTime(date('m/d/Y', strtotime('+1 day')));
        $expectedAgeAfter = $birthDateAfter->diff(new DateTime())->y;
        $this->assertSame(
            $expectedAgeAfter,
            $nowAfterBirthday->age,
            'the age must be correct just after the birthday'
        );
    }

    /**
     * Test it handle different time zones correctly.
     *
     * @return void
     */
    public function testItHandlesDifferentTimeZonesCorrectly(): void
    {
        $nowUTC      = new Now('01/01/2000', 'UTC');
        $nowRM       = new Now('01/01/2000', 'Europe/Rome');
        $birthDate   = new DateTime('01/01/2000');
        $expectedAge = $birthDate->diff(new DateTime())->y;

        $this->assertSame(
            $expectedAge,
            $nowUTC->age,
            'the age must be correct in UTC'
        );

        $this->assertSame(
            $expectedAge,
            $nowRM->age,
            'the age must be correct in Europe/Rome'
        );
    }

    /**
     * Test it can get from private property.
     *
     * @return void
     */
    public function testItCanGetFromPrivateProperty(): void
    {
        $now = new Now();
        $now->day(12);

        $this->assertEquals(12, $now->day);
    }

    /**
     * Test it can set from property.
     *
     * @return void
     * @noinspection PhpConditionAlreadyCheckedInspection - $now->day is managed via __get()
     */
    public function testItCanSetFromProperty(): void
    {
        $now      = new Now();
        $now->day = 12;

        $this->assertEquals(12, $now->day);
    }

    /**
     * Test it can use private property using setter and getter.
     *
     * @return void
     * @noinspection PhpConditionAlreadyCheckedInspection - $now->XX is managed via __get()
     */
    public function testItCanUsePrivatePropertyUsingSetterAndGetter(): void
    {
        $now = new Now();

        $now->year = 2022;
        $this->assertEquals(2022, $now->year);

        $now->month = 1;
        $this->assertEquals(1, $now->month);

        $now->day = 11;
        $this->assertEquals(11, $now->day);

        $now->hour = 1;
        $this->assertEquals(1, $now->hour);

        $now->minute = 27;
        $this->assertEquals(27, $now->minute);

        $now->second = 0;
        $this->assertEquals(0, $now->second);

        $this->assertEquals('January', $now->monthName);
        $this->assertEquals('Tuesday', $now->dayName);
        $this->assertEquals('Tue', $now->shortDay);
        $this->assertEquals('UTC', $now->timeZone);

        $this->lessThan($now->age);
    }

    /**
     * Test it throw when setting timestamp.
     *
     * @return void
     */
    public function testItThrowsWhenSettingTimestamp(): void
    {
        $now = new Now();
        $this->expectException(PropertyNotSettableException::class);
        $now->timestamp = time();
    }

    /**
     * Test it throw when setting month name.
     *
     * @return void
     */
    public function testThrowsWhenSettingMonthName(): void
    {
        $now = new Now();
        $this->expectException(PropertyNotSettableException::class);
        $now->monthName = 'June';
    }

    /**
     * Test it throw when setting day name.
     *
     * @return void
     */
    public function testThrowsWhenSettingDayName(): void
    {
        $now = new Now();
        $this->expectException(PropertyNotSettableException::class);
        $now->dayName = 'Tuesday';
    }

    /**
     * Test it throw when setting timezone.
     *
     * @return void
     */
    public function testThrowsWhenSettingTimeZone(): void
    {
        $now = new Now();
        $this->expectException(PropertyNotSettableException::class);
        $now->timeZone = 'UTC';
    }

    /**
     * Test it throw when setting age.
     *
     * @return void
     */
    public function testThrowsWhenSettingAge(): void
    {
        $now = new Now();
        $this->expectException(PropertyNotSettableException::class);
        $now->age = 27;
    }

    /**
     * Test it throw when get undefined property.
     *
     * @return void
     */
    public function testItThrowWhenGetUndefinedProperty(): void
    {
        $now = new Now();

        $this->expectException(PropertyNotExistException::class);
        $now->notExistProperty;
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
     * Test it can return formatted time with standard time.
     *
     * @return void
     */
    public function testItCanReturnFormatedTimeWithStandardTime(): void
    {
        $now = new Now('29-01-2023', 'UTC');

        $this->assertEquals('Sunday, 29-Jan-2023 00:00:00 UTC', $now->formatCOOKIE());
    }
}
