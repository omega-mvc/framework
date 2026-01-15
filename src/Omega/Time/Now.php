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
use DateTime;
use DateTimeZone;
use Omega\Time\Exceptions\PropertyNotExistException;
use Omega\Time\Exceptions\PropertyNotSettableException;
use Omega\Time\Traits\DateTimeFormatTrait;

use function floor;
use function implode;
use function max;
use function method_exists;
use function property_exists;
use function strtotime;
use function time;

/**
 * Now class - represents a specific point in time with convenient access to date and time components.
 *
 * This class wraps a DateTime object and exposes properties like year, month, day, hour, minute,
 * second, as well as formatted names for month and day. It also provides helper methods to check
 * if the current instance matches specific time conditions (e.g., isNextMonth(), isSaturday()).
 *
 * It is used throughout Omega Time package to manipulate, format, and inspect dates easily.
 *
 * @category  Omega
 * @package   Time
 * @link      https://omega-mvc.github.io
 * @author    Adriano Giovannini <agisoftt@gmail.com>
 * @copyright Copyright (c) 2025 - 2026 Adriano Giovannini (https://omega-mvc.github.io)
 * @license   https://www.gnu.org/licenses/gpl-3.0-standalone.html     GPL V3.0+
 * @version   2.0.0
 *
 * @property int    $timestamp         The Unix timestamp of the current time.
 * @property int    $year              The year of the current date.
 * @property int    $month             The month number (1-12) of the current date.
 * @property int    $day               The day of the month (1-31) of the current date.
 * @property int    $hour              The hour (0-23) of the current time.
 * @property int    $minute            The minute (0-59) of the current time.
 * @property int    $second            The second (0-59) of the current time.
 * @property string $monthName         The full month name (e.g., "January").
 * @property string $dayName           The full day name (e.g., "Monday").
 * @property string $shortDay          The abbreviated day name (e.g., "Mon").
 * @property string $timeZone          The timezone identifier (e.g., "UTC", "Europe/Rome").
 * @property int    $age               The calculated age in years from this timestamp until now.
 * @property mixed  $notExistProperty  Placeholder to demonstrate exception when property does not exist.
 */
class Now
{
    use DateTimeFormatTrait;

    /** @var int|false Current Unix timestamp of the object. */
    private int|false $timestamp;

    /** @var DateTime The internal DateTime instance representing the current time. */
    private DateTime $date;

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
        if (null !== $timeZone) {
            $timeZone = new DateTimeZone($timeZone);
        }
        $this->date = new DateTime($dateFormat, $timeZone);

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
        return implode('T', [
            $this->date->format('Y-m-d'),
            $this->date->format('H:i:s'),
        ]);
    }

    /**
     * Retrieves a private property by name.
     *
     * @param string $name The property name to get.
     * @return mixed The value of the property.
     * @throws PropertyNotExistException If the property does not exist in the class.
     */
    public function __get(string $name): mixed
    {
        if (property_exists($this, $name)) {
            return $this->{$name};
        }

        throw new PropertyNotExistException($name);
    }

    /**
     * Sets a property using the corresponding setter method.
     *
     * Only properties with dedicated setter methods can be set.
     *
     * @param string $name  The property name to set.
     * @param mixed  $value The value to assign.
     * @return void
     * @throws PropertyNotSettableException If the property cannot be set.
     */
    public function __set(string $name, mixed $value): void
    {
        if (method_exists($this, $name) && property_exists($this, $name)) {
            $this->{$name}($value);
            return;
        }

        throw new PropertyNotSettableException($name);
    }

    /**
     * Refreshes all properties based on the internal DateTime object.
     *
     * Updates timestamp, year, month, day, hour, minute, second, month/day names, timezone, and age.
     *
     * @return void
     */
    private function refresh(): void
    {
        $this->timestamp = $this->date->getTimestamp();
        $this->year      = (int) $this->date->format('Y');
        $this->month     = (int) $this->date->format('n');
        $this->day       = (int) $this->date->format('d');
        $this->hour      = (int) $this->date->format('H');
        $this->minute    = (int) $this->date->format('i');
        $this->second    = (int) $this->date->format('s');

        $this->monthName = $this->date->format('F');
        $this->dayName   = $this->date->format('l');
        $this->timeZone  = $this->date->format('e');
        $this->shortDay  = $this->date->format('D');

        $this->age = max(0, (int) floor((time() - $this->timestamp) / (365.25 * 24 * 60 * 60)));
    }

    /**
     * Returns a formatted string for a given timestamp.
     *
     * @param string $format    The date format (compatible with DateTime::format).
     * @param int    $timestamp The Unix timestamp to format.
     * @return string The formatted date-time string.
     */
    private function current(string $format, int $timestamp): string
    {
        $date = $this->date;
        return $date->setTimestamp($timestamp)->format($format);
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
     * Sets the year for the current date-time and refreshes all properties.
     *
     * @param int $year The year to set (e.g., 2025).
     * @return self The current instance for method chaining.
     */
    public function year(int $year): self
    {
        $this->date->setDate($year, $this->month, $this->day)
            ->setTime($this->hour, $this->minute, $this->second);

        $this->refresh();

        return $this;
    }

    /**
     * Sets the month for the current date-time and refreshes all properties.
     *
     * @param int $month The month to set (1-12).
     * @return self The current instance for method chaining.
     */
    public function month(int $month): self
    {
        $this->date->setDate($this->year, $month, $this->day)
            ->setTime($this->hour, $this->minute, $this->second);

        $this->refresh();

        return $this;
    }

    /**
     * Sets the day for the current date-time and refreshes all properties.
     *
     * @param int $day The day of the month to set (1-31).
     * @return self The current instance for method chaining.
     */
    public function day(int $day): self
    {
        $this->date->setDate($this->year, $this->month, $day)
            ->setTime($this->hour, $this->minute, $this->second);

        $this->refresh();

        return $this;
    }

    /**
     * Sets the hour for the current date-time and refreshes all properties.
     *
     * @param int $hour The hour to set (0-23).
     * @return self The current instance for method chaining.
     */
    public function hour(int $hour): self
    {
        $this->date->setDate($this->year, $this->month, $this->day)
            ->setTime($hour, $this->minute, $this->second);

        $this->refresh();

        return $this;
    }

    /**
     * Sets the minute for the current date-time and refreshes all properties.
     *
     * @param int $minute The minute to set (0-59).
     * @return self The current instance for method chaining.
     */
    public function minute(int $minute): self
    {
        $this->date->setDate($this->year, $this->month, $this->day)
            ->setTime($this->hour, $minute, $this->second);

        $this->refresh();

        return $this;
    }

    /**
     * Sets the second for the current date-time and refreshes all properties.
     *
     * @param int $second The second to set (0-59).
     * @return self The current instance for method chaining.
     */
    public function second(int $second): self
    {
        $this->date->setDate($this->year, $this->month, $this->day)
            ->setTime($this->hour, $this->minute, $second);

        $this->refresh();

        return $this;
    }

    /**
     * Checks if the current month is January.
     *
     * @return bool True if the month is January, false otherwise.
     */
    public function isJan(): bool
    {
        return $this->date->format('M') === 'Jan';
    }

    /**
     * Checks if the current month is February.
     *
     * @return bool True if the month is February, false otherwise.
     */
    public function isFeb(): bool
    {
        return $this->date->format('M') === 'Feb';
    }

    /**
     * Checks if the current month is March.
     *
     * @return bool True if the month is March, false otherwise.
     */
    public function isMar(): bool
    {
        return $this->date->format('M') === 'Mar';
    }

    /**
     * Checks if the current month is April.
     *
     * @return bool True if the month is April, false otherwise.
     */
    public function isApr(): bool
    {
        return $this->date->format('M') === 'Apr';
    }

    /**
     * Checks if the current month is May.
     *
     * @return bool True if the month is May, false otherwise.
     */
    public function isMay(): bool
    {
        return $this->date->format('M') === 'May';
    }

    /**
     * Checks if the current month is June.
     *
     * @return bool True if the month is June, false otherwise.
     */
    public function isJun(): bool
    {
        return $this->date->format('M') === 'Jun';
    }

    /**
     * Checks if the current month is July.
     *
     * @return bool True if the month is July, false otherwise.
     */
    public function isJul(): bool
    {
        return $this->date->format('M') === 'Jul';
    }

    /**
     * Checks if the current month is August.
     *
     * @return bool True if the month is August, false otherwise.
     */
    public function isAug(): bool
    {
        return $this->date->format('M') === 'Aug';
    }

    /**
     * Checks if the current month is September.
     *
     * @return bool True if the month is September, false otherwise.
     */
    public function isSep(): bool
    {
        return $this->date->format('M') === 'Sep';
    }

    /**
     * Checks if the current month is October.
     *
     * @return bool True if the month is October, false otherwise.
     */
    public function isOct(): bool
    {
        return $this->date->format('M') === 'Oct';
    }

    /**
     * Checks if the current month is November.
     *
     * @return bool True if the month is November, false otherwise.
     */
    public function isNov(): bool
    {
        return $this->date->format('M') === 'Nov';
    }

    /**
     * Checks if the current month is December.
     *
     * @return bool True if the month is December, false otherwise.
     */
    public function isDec(): bool
    {
        return $this->date->format('M') === 'Dec';
    }

    /**
     * Checks if the current day is Monday.
     *
     * @return bool True if the day is Monday, false otherwise.
     */
    public function isMonday(): bool
    {
        return $this->date->format('D') === 'Mon';
    }

    /**
     * Checks if the current day is Tuesday.
     *
     * @return bool True if the day is Tuesday, false otherwise.
     */
    public function isTuesday(): bool
    {
        return $this->date->format('D') === 'Tue';
    }

    /**
     * Checks if the current day is Wednesday.
     *
     * @return bool True if the day is Wednesday, false otherwise.
     */
    public function isWednesday(): bool
    {
        return $this->date->format('D') === 'Wed';
    }

    /**
     * Checks if the current day is Thursday.
     *
     * @return bool True if the day is Thursday, false otherwise.
     */
    public function isThursday(): bool
    {
        return $this->date->format('D') === 'Thu';
    }

    /**
     * Checks if the current day is Friday.
     *
     * @return bool True if the day is Friday, false otherwise.
     */
    public function isFriday(): bool
    {
        return $this->date->format('D') === 'Fri';
    }

    /**
     * Checks if the current day is Saturday.
     *
     * @return bool True if the day is Saturday, false otherwise.
     */
    public function isSaturday(): bool
    {
        return $this->date->format('D') === 'Sat';
    }

    /**
     * Checks if the current day is Sunday.
     *
     * @return bool True if the day is Sunday, false otherwise.
     */
    public function isSunday(): bool
    {
        return $this->date->format('D') === 'Sun';
    }

    /**
     * Checks if the current year is next year.
     *
     * @return bool True if the year is next year, false otherwise.
     */
    public function isNextYear(): bool
    {
        $time = strtotime('next year');
        return $this->current('Y', $time) == $this->year;
    }

    /**
     * Checks if the current month is next month.
     *
     * @return bool True if the month is next month, false otherwise.
     */
    public function isNextMonth(): bool
    {
        $time = strtotime('next month');
        return $this->current('n', $time) == $this->month;
    }

    /**
     * Checks if the current day is the next day.
     *
     * @return bool True if the day is the next day, false otherwise.
     */
    public function isNextDay(): bool
    {
        $time = strtotime('next day');
        return $this->current('d', $time) == $this->day;
    }

    /**
     * Checks if the current hour is the next hour.
     *
     * @return bool True if the hour is the next hour, false otherwise.
     */
    public function isNextHour(): bool
    {
        $time = strtotime('next hour');
        return $this->current('H', $time) == $this->hour;
    }

    /**
     * Checks if the current minute is the next minute.
     *
     * @return bool True if the minute is the next minute, false otherwise.
     */
    public function isNextMinute(): bool
    {
        $time = strtotime('next minute');
        return $this->current('i', $time) == $this->minute;
    }

    /**
     * Determines whether this instance represents the previous calendar year.
     *
     * @return bool True if the year matches the previous calendar year, false otherwise.
     */
    public function isLastYear(): bool
    {
        $time = strtotime('last year');
        return $this->current('Y', $time) == $this->year;
    }

    /**
     * Determines whether this instance represents the previous calendar month.
     *
     * @return bool True if the month matches the previous calendar month, false otherwise.
     */
    public function isLastMonth(): bool
    {
        $time = strtotime('last month');
        return $this->current('n', $time) == $this->month;
    }

    /**
     * Checks if the current day is the previous day.
     *
     * @return bool True if the day is last day, false otherwise.
     */
    public function isLastDay(): bool
    {
        $time = strtotime('last day');
        return $this->current('d', $time) == $this->day;
    }

    /**
     * Checks if the current hour is the previous hour.
     *
     * @return bool True if the hour is last hour, false otherwise.
     */
    public function isLastHour(): bool
    {
        $time = strtotime('last hour');
        return $this->current('H', $time) == $this->hour;
    }

    /**
     * Checks if the current minute is the previous minute.
     *
     * @return bool True if the minute is last minute, false otherwise.
     */
    public function isLastMinute(): bool
    {
        $time = strtotime('last minute');
        return $this->current('i', $time) == $this->minute;
    }
}
