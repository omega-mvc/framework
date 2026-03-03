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

/**
 * @noinspection PhpUnused
 * @noinspection PhpPrivateFieldCanBeLocalVariableInspection
 */

declare(strict_types=1);

namespace Omega\Time;

use DateInvalidTimeZoneException;
use DateMalformedStringException;
use DateTimeImmutable;
use DateTimeZone;
use Omega\Time\Traits\DateTimeFormatTrait;

/**
 * Now class - represents a precise point in time with easy access to date/time components.
 *
 * This immutable class wraps a DateTimeImmutable object and exposes:
 * - Year, month, day, hour, minute, second
 * - Full month/day names and 3-letter day abbreviations
 * - Day of week as integer (1=Monday, 7=Sunday)
 * - Timezone identifier and Unix timestamp
 * - Age calculated from this date until now
 *
 * Provides fluent setters (year(), month(), day(), etc.) that return a new instance
 * and helper methods to check temporal conditions (isNextMonth(), isMonday(), etc.).
 *
 * @category  Omega
 * @package   Time
 * @link      https://omega-mvc.github.io
 * @author    Adriano Giovannini <agisoftt@gmail.com>
 * @copyright Copyright (c) 2025 - 2026 Adriano Giovannini
 * @license   https://www.gnu.org/licenses/gpl-3.0-standalone.html GPL V3.0+
 * @version   2.0.0
 *
 * @property int    $timestamp         Unix timestamp of this date-time.
 * @property int    $year              Year of the date.
 * @property int    $month             Month number (1-12).
 * @property int    $day               Day of the month (1-31).
 * @property int    $hour              Hour (0-23).
 * @property int    $minute            Minute (0-59).
 * @property int    $second            Second (0-59).
 * @property string $monthName         Full month name (e.g., "January").
 * @property string $dayName           Full day name (e.g., "Monday").
 * @property string $shortDay          3-letter day abbreviation (e.g., "Mon").
 * @property int    $dayOfWeek         Day of the week as integer, 1=Monday, 7=Sunday.
 * @property string $timeZone          Timezone identifier (e.g., "UTC", "Europe/Rome").
 * @property int    $age               Age in years from this date until now.
 */
class Now
{
    use DateTimeFormatTrait;

    /** @var int|false Current Unix timestamp of the object. */
    private int|false $timestamp;

    /** @var DateTimeImmutable The internal immutable DateTime instance. */
    private DateTimeImmutable $date;

    /** @var int Year of the date. */
    private int $year;

    /** @var int Month of the date (1-12). */
    private int $month;

    /** @var int Day of the month. */
    private int $day;

    /** @var int Hour of the time (0-23). */
    private int $hour;

    /** @var int Minute of the time (0-59). */
    private int $minute;

    /** @var int Second of the time (0-59). */
    private int $second;

    /** @var string Full month name. */
    private string $monthName;

    /** @var string Full day name. */
    private string $dayName;

    /** @var string Short day name (3-letter abbreviation). */
    private string $shortDay;

    /** @var string Timezone identifier. */
    private string $timeZone;

    /** @var int Age in years relative to the timestamp. */
    private int $age;

    /** @var int Day of the week as integer, 1=Monday, 7=Sunday */
    private int $dayOfWeek;

    /**
     * Constructs a new Now instance with a specific date and optional timezone.
     *
     * Initializes the internal DateTime object and refreshes all related properties.
     *
     * @param string      $dateFormat The date string or 'now' for current time.
     * @param string|null $timeZone   Optional timezone identifier (e.g., "UTC").
     * @return void
     * @throws DateInvalidTimeZoneException Thrown when a provided timezone is invalid.
     * @throws DateMalformedStringException Thrown when a date string cannot be parsed correctly.
     */
    public function __construct(string $dateFormat = 'now', ?string $timeZone = null)
    {
        $tz = $timeZone ? new DateTimeZone($timeZone) : null;
        $this->date = new DateTimeImmutable($dateFormat, $tz);
        $this->refresh();
    }

    /**
     * Returns the ISO-like string representation of the current date and time.
     *
     * Format: "YYYY-MM-DDTHH:MM:SS".
     *
     * @return string The formatted date-time string.
     */
    public function __toString(): string
    {
        return $this->date->format('Y-m-d\TH:i:s');
    }

    /**
     * Refreshes all properties based on the internal DateTime object.
     *
     * Updates timestamp, year, month, day, hour, minute, second, month/day names, timezone, and age.
     *
     * @return void
     * @throws DateMalformedStringException Thrown when a date string cannot be parsed correctly.
     */
    private function refresh(): void
    {
        $parts = explode('|', $this->date->format('Y|n|d|H|i|s|F|l|D|e|U|N'));

        $this->year      = (int)$parts[0];
        $this->month     = (int)$parts[1];
        $this->day       = (int)$parts[2];
        $this->hour      = (int)$parts[3];
        $this->minute    = (int)$parts[4];
        $this->second    = (int)$parts[5];
        $this->monthName = $parts[6];
        $this->dayName   = $parts[7];
        $this->shortDay  = $parts[8];
        $this->timeZone  = $parts[9];
        $this->timestamp = (int)$parts[10];
        $this->dayOfWeek = (int)$parts[11];

        $today = new DateTimeImmutable('now', $this->date->getTimezone());
        $this->age = ($this->timestamp > $today->getTimestamp()) ? 0 : $this->date->diff($today)->y;
    }

    /**
     * Formats the current date-time using a custom format string.
     *
     * @param string $format The date format (compatible with DateTime::format).
     * @return string The formatted date-time string.
     */
    public function format(string $format): string
    {
        return $this->date->format($format);
    }

    /**
     * Get the year of the date.
     *
     * @return int Year value (e.g., 2026)
     */
    public function getYear(): int
    {
        return $this->year;
    }

    /**
     * Get the month number of the date.
     *
     * @return int Month value (1-12)
     */
    public function getMonth(): int
    {
        return $this->month;
    }

    /**
     * Get the day of the month.
     *
     * @return int Day value (1-31)
     */
    public function getDay(): int
    {
        return $this->day;
    }

    /**
     * Get the hour of the time.
     *
     * @return int Hour value (0-23)
     */
    public function getHour(): int
    {
        return $this->hour;
    }

    /**
     * Get the minute of the time.
     *
     * @return int Minute value (0-59)
     */
    public function getMinute(): int
    {
        return $this->minute;
    }

    /**
     * Get the second of the time.
     *
     * @return int Second value (0-59)
     */
    public function getSecond(): int
    {
        return $this->second;
    }

    /**
     * Get the full name of the month.
     *
     * @return string Month name (e.g., "January")
     */
    public function getMonthName(): string
    {
        return $this->monthName;
    }

    /**
     * Get the full name of the day.
     *
     * @return string Day name (e.g., "Monday")
     */
    public function getDayName(): string
    {
        return $this->dayName;
    }

    /**
     * Get the abbreviated 3-letter name of the day.
     *
     * @return string Short day name (e.g., "Mon")
     */
    public function getShortDay(): string
    {
        return $this->shortDay;
    }

    /**
     * Get the timezone identifier of the date-time.
     *
     * @return string Timezone (e.g., "UTC", "Europe/Rome")
     */
    public function getTimeZone(): string
    {
        return $this->timeZone;
    }

    /**
     * Get the Unix timestamp of the date-time.
     *
     * @return int Unix timestamp
     */
    public function getTimestamp(): int
    {
        return $this->timestamp;
    }

    /**
     * Get the day of the week as an integer.
     *
     * @return int Day of week (1=Monday, 7=Sunday)
     */
    public function getDayOfWeek(): int
    {
        return $this->dayOfWeek;
    }

    /**
     * Get the age in years relative to the current date.
     *
     * @return int Age in years
     */
    public function getAge(): int
    {
        return $this->age;
    }

    /**
     * Sets the year for the current date-time and refreshes all properties.
     *
     * @param int $year The year to set (e.g., 2025).
     * @return self The current instance for method chaining.
     * @throws DateMalformedStringException Thrown when a date string cannot be parsed correctly.
     */
    public function setYear(int $year): self
    {
        $new = clone $this;

        $new->date = $this->date->setDate($year, $this->month, $this->day);

        $new->refresh();

        return $new;
    }

    /**
     * Sets the month for the current date-time and refreshes all properties.
     *
     * @param int $month The month to set (1-12).
     * @return self The current instance for method chaining.
     * @throws DateMalformedStringException Thrown when a date string cannot be parsed correctly.
     */
    public function setMonth(int $month): self
    {
        $new = clone $this;

        $new->date = $this->date->setDate($this->year, $month, $this->day);

        $new->refresh();

        return $new;
    }

    /**
     * Sets the day for the current date-time and refreshes all properties.
     *
     * @param int $day The day of the month to set (1-31).
     * @return self The current instance for method chaining.
     * @throws DateMalformedStringException Thrown when a date string cannot be parsed correctly.
     */
    public function setDay(int $day): self
    {
        $new = clone $this;

        $new->date = $this->date->setDate($this->year, $this->month, $day);

        $new->refresh();

        return $new;
    }

    /**
     * Sets the hour for the current date-time and refreshes all properties.
     *
     * @param int $hour The hour to set (0-23).
     * @return self The current instance for method chaining.
     * @throws DateMalformedStringException Thrown when a date string cannot be parsed correctly.
     */
    public function setHour(int $hour): self
    {
        $new = clone $this;

        $new->date = $this->date->setTime($hour, $this->minute, $this->second);

        $new->refresh();

        return $new;
    }

    /**
     * Sets the minute for the current date-time and refreshes all properties.
     *
     * @param int $minute The minute to set (0-59).
     * @return self The current instance for method chaining.
     * @throws DateMalformedStringException Thrown when a date string cannot be parsed correctly.
     */
    public function setMinute(int $minute): self
    {
        $new = clone $this;

        $new->date = $this->date->setTime($this->hour, $minute, $this->second);

        $new->refresh();

        return $new;
    }

    /**
     * Sets the second for the current date-time and refreshes all properties.
     *
     * @param int $second The second to set (0-59).
     * @return self The current instance for method chaining.
     * @throws DateMalformedStringException Thrown when a date string cannot be parsed correctly.
     */
    public function setSecond(int $second): self
    {
        $new = clone $this;

        $new->date = $this->date->setTime($this->hour, $this->minute, $second);

        $new->refresh();

        return $new;
    }

    /**
     * Checks if the current month is January.
     *
     * @return bool True if the month is January, false otherwise.
     */
    public function isJan(): bool
    {
        return $this->month === 1;
    }

    /**
     * Checks if the current month is February.
     *
     * @return bool True if the month is February, false otherwise.
     */
    public function isFeb(): bool
    {
        return $this->month === 2;
    }

    /**
     * Checks if the current month is March.
     *
     * @return bool True if the month is March, false otherwise.
     */
    public function isMar(): bool
    {
        return $this->month === 3;
    }

    /**
     * Checks if the current month is April.
     *
     * @return bool True if the month is April, false otherwise.
     */
    public function isApr(): bool
    {
        return $this->month === 4;
    }

    /**
     * Checks if the current month is May.
     *
     * @return bool True if the month is May, false otherwise.
     */
    public function isMay(): bool
    {
        return $this->month === 5;
    }

    /**
     * Checks if the current month is June.
     *
     * @return bool True if the month is June, false otherwise.
     */
    public function isJun(): bool
    {
        return $this->month === 6;
    }

    /**
     * Checks if the current month is July.
     *
     * @return bool True if the month is July, false otherwise.
     */
    public function isJul(): bool
    {
        return $this->month === 7;
    }

    /**
     * Checks if the current month is August.
     *
     * @return bool True if the month is August, false otherwise.
     */
    public function isAug(): bool
    {
        return $this->month === 8;
    }

    /**
     * Checks if the current month is September.
     *
     * @return bool True if the month is September, false otherwise.
     */
    public function isSep(): bool
    {
        return $this->month === 9;
    }

    /**
     * Checks if the current month is October.
     *
     * @return bool True if the month is October, false otherwise.
     */
    public function isOct(): bool
    {
        return $this->month === 10;
    }

    /**
     * Checks if the current month is November.
     *
     * @return bool True if the month is November, false otherwise.
     */
    public function isNov(): bool
    {
        return $this->month === 11;
    }

    /**
     * Checks if the current month is December.
     *
     * @return bool True if the month is December, false otherwise.
     */
    public function isDec(): bool
    {
        return $this->month === 12;
    }

    /**
     * Checks if the current day is Monday.
     *
     * @return bool True if the day is Monday, false otherwise.
     */
    public function isMonday(): bool
    {
        return $this->dayOfWeek === 1;
    }

    /**
     * Checks if the current day is Tuesday.
     *
     * @return bool True if the day is Tuesday, false otherwise.
     */
    public function isTuesday(): bool
    {
        return $this->dayOfWeek === 2;
    }

    /**
     * Checks if the current day is Wednesday.
     *
     * @return bool True if the day is Wednesday, false otherwise.
     */
    public function isWednesday(): bool
    {
        return $this->dayOfWeek === 3;
    }

    /**
     * Checks if the current day is Thursday.
     *
     * @return bool True if the day is Thursday, false otherwise.
     */
    public function isThursday(): bool
    {
        return $this->dayOfWeek === 4;
    }

    /**
     * Checks if the current day is Friday.
     *
     * @return bool True if the day is Friday, false otherwise.
     */
    public function isFriday(): bool
    {
        return $this->dayOfWeek === 5;
    }

    /**
     * Checks if the current day is Saturday.
     *
     * @return bool True if the day is Saturday, false otherwise.
     */
    public function isSaturday(): bool
    {
        return $this->dayOfWeek === 6;
    }

    /**
     * Checks if the current day is Sunday.
     *
     * @return bool True if the day is Sunday, false otherwise.
     */
    public function isSunday(): bool
    {
        return $this->dayOfWeek === 7;
    }

    /**
     * Checks if the current year is next year.
     *
     * @return bool True if the year is next year, false otherwise.
     */
    public function isNextYear(): bool
    {
        return $this->year === (int)date('Y') + 1;
    }

    /**
     * Checks if the current month is next month.
     *
     * @return bool True if the month is next month, false otherwise.
     */
    public function isNextMonth(): bool
    {
        $nextMonth = new DateTimeImmutable('first day of next month');

        return $this->date->format('Y-m') === $nextMonth->format('Y-m');
    }

    /**
     * Checks if the current day is the next day.
     *
     * @return bool True if the day is the next day, false otherwise.
     */
    public function isNextDay(): bool
    {
        $tomorrow = new DateTimeImmutable('tomorrow');

        return $this->date->format('Y-m-d') === $tomorrow->format('Y-m-d');
    }

    /**
     * Checks if the current hour is the next hour.
     *
     * @return bool True if the hour is the next hour, false otherwise.
     */
    public function isNextHour(): bool
    {
        $nextHour = new DateTimeImmutable('+1 hour');

        return $this->date->format('Y-m-d H') === $nextHour->format('Y-m-d H');
    }

    /**
     * Checks if the current minute is the next minute.
     *
     * @return bool True if the minute is the next minute, false otherwise.
     */
    public function isNextMinute(): bool
    {
        $nextMinute = new DateTimeImmutable('+1 minute');

        return $this->date->format('Y-m-d H:i') === $nextMinute->format('Y-m-d H:i');
    }

    /**
     * Determines whether this instance represents the previous calendar year.
     *
     * @return bool True if the year matches the previous calendar year, false otherwise.
     */
    public function isLastYear(): bool
    {
        return $this->year === (int)date('Y') - 1;
    }

    /**
     * Determines whether this instance represents the previous calendar month.
     *
     * @return bool True if the month matches the previous calendar month, false otherwise.
     */
    public function isLastMonth(): bool
    {
        $lastMonth = new DateTimeImmutable('first day of last month');

        return $this->date->format('Y-m') === $lastMonth->format('Y-m');
    }

    /**
     * Checks if the current day is the previous day.
     *
     * @return bool True if the day is last day, false otherwise.
     */
    public function isLastDay(): bool
    {
        $yesterday = new DateTimeImmutable('yesterday');

        return $this->date->format('Y-m-d') === $yesterday->format('Y-m-d');
    }

    /**
     * Checks if the current hour is the previous hour.
     *
     * @return bool True if the hour is last hour, false otherwise.
     */
    public function isLastHour(): bool
    {
        $lastHour = new DateTimeImmutable('-1 hour');

        return $this->date->format('Y-m-d H') === $lastHour->format('Y-m-d H');
    }

    /**
     * Checks if the current minute is the previous minute.
     *
     * @return bool True if the minute is last minute, false otherwise.
     */
    public function isLastMinute(): bool
    {
        $lastMinute = new DateTimeImmutable('-1 minute');

        return $this->date->format('Y-m-d H:i') === $lastMinute->format('Y-m-d H:i');
    }
}
