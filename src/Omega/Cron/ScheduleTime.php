<?php

/**
 * Part of Omega - Cron Package.
 *
 * @link      https://omega-mvc.github.io
 * @author    Adriano Giovannini <agisoftt@gmail.com>
 * @copyright Copyright (c) 2025 - 2026 Adriano Giovannini (https://omega-mvc.github.io)
 * @license   https://www.gnu.org/licenses/gpl-3.0-standalone.html     GPL V3.0+
 * @version   2.0.0
 */

declare(strict_types=1);

namespace Omega\Cron;

use Closure;
use Throwable;

use function call_user_func;
use function date;
use function microtime;
use function range;
use function round;

/**
 * Class ScheduleTime
 *
 * Represents a single scheduled task with its execution time, callback, retry policy,
 * and optional logging. This class supports flexible scheduling intervals including
 * just-in-time, hourly, daily, weekly, and monthly tasks, as well as anonymous execution.
 *
 * Each instance tracks execution attempts, failure states, and optional skipping rules.
 *
 * @category  Omega
 * @package   Cron
 * @link      https://omega-mvc.github.io
 * @author    Adriano Giovannini <agisoftt@gmail.com>
 * @copyright Copyright (c) 2025 - 2026 Adriano Giovannini (https://omega-mvc.github.io)
 * @license   https://www.gnu.org/licenses/gpl-3.0-standalone.html     GPL V3.0+
 * @version   2.0.0
 */
class ScheduleTime
{
    /** @var Closure The callback function to execute when the schedule is due. */
    private Closure $callBack;

    /** @var array Parameters to pass to the callback function. */
    private array $params;

    /** @var int Timestamp representing the current time for this schedule. */
    private int $time;

    /** @var string Name of the event; default is 'anonymously'. */
    public string $eventName = 'anonymously' { // phpcs:ignore
        get {
            return $this->eventName; // phpcs:ignore
        }
    }

    /** @var array<int, array<string,int|string>|int> The expected times for cron execution. */
    private array $timeExpect { // phpcs:ignore
        get {
            return $this->timeExpect; // phpcs:ignore
        }
    }

    /** @var string Name of the cron time type (e.g., 'hourly', 'daily'). */
    public string $timeName = '' { // phpcs:ignore
        get {
            return $this->timeName; // phpcs:ignore
        }
    }

    /** @var bool Whether the task runs anonymously without logging. */
    public bool $anonymously  = false { // phpcs:ignore
        get {
            return $this->anonymously; // phpcs:ignore
        }
    }

    /** @var bool Tracks if the cron task execution failed. */
    private bool $isFail = false { // phpcs:ignore
        get {
            return $this->isFail; // phpcs:ignore
        }
    }

    /** @var int Maximum retry attempts allowed for this schedule. */
    private int $retryAttempts = 0;

    /** @var bool Whether the schedule should retry if a condition evaluates true. */
    private bool $retryCondition = false;

    /** @var bool Whether to skip this schedule execution under certain conditions. */
    private bool $skip = false;

    /**
     * Logger instance used to handle interpolated debug output during execution.
     * Nullable to avoid external dependencies.
     *
     * @var InterpolateInterface|null
     */
    private ?InterpolateInterface $logger = null;

    /**
     * ScheduleTime constructor.
     *
     * @param Closure $callBack The callback to execute when the schedule is due.
     * @param array $params Optional parameters for the callback.
     * @param int $timestamp Base timestamp to determine execution time.
     * @return void
     */
    public function __construct(Closure $callBack, array $params, int $timestamp)
    {
        $this->callBack   = $callBack;
        $this->params     = $params;
        $this->time       = $timestamp;
        $this->timeExpect = [
            [
                'D' => date('D', $this->time),
                'd' => date('d', $this->time),
                'h' => date('H', $this->time),
                'm' => date('i', $this->time),
            ],
        ];
    }

    /**
     * Set the name of the event for this scheduled task.
     *
     * @param string $val Name of the event.
     * @return $this Fluent interface.
     */
    public function eventName(string $val): self
    {
        $this->eventName = $val;

        return $this;
    }

    /**
     * Mark the schedule to run anonymously, disabling logging.
     *
     * @param bool $runAsAnonymously True to run without logging.
     * @return $this Fluent interface.
     */
    public function anonymously(bool $runAsAnonymously = true): self
    {
        $this->anonymously = $runAsAnonymously;

        return $this;
    }

    /**
     * Get the remaining retry attempts for this schedule.
     *
     * @return int Number of retry attempts left.
     */
    public function retryAttempts(): int
    {
        return $this->retryAttempts;
    }

    /**
     * Set the number of retry attempts for this schedule.
     *
     * @param int $attempt Maximum number of retries allowed.
     * @return $this Fluent interface.
     */
    public function retry(int $attempt): self
    {
        $this->retryAttempts = $attempt;

        return $this;
    }

    /**
     * Set a conditional flag to retry this schedule only if the condition is true.
     *
     * @param bool $condition True to enable retry based on condition.
     * @return $this Fluent interface.
     */
    public function retryIf(bool $condition): self
    {
        $this->retryCondition = $condition;

        return $this;
    }

    /**
     * Check if the schedule is set to retry based on condition.
     *
     * @return bool True if conditional retry is enabled.
     */
    public function isRetry(): bool
    {
        return $this->retryCondition;
    }

    /**
     * Conditionally skip the schedule execution.
     *
     * @param bool|Closure(): bool $skipWhen True to skip, or a closure returning bool.
     * @return $this Fluent interface.
     */
    public function skip(bool|Closure $skipWhen): self
    {
        if ($skipWhen instanceof Closure) {
            $skipWhen = $skipWhen();
        }

        $this->skip = $skipWhen;

        return $this;
    }

    /**
     * Execute the scheduled callback if the schedule is due and not skipped.
     *
     * Tracks execution time, handles retry attempts, updates failure state,
     * and optionally logs execution via the logger if not anonymous.
     *
     * @return void
     */
    public function expect(): void
    {
        if ($this->isDue() && false === $this->skip) {
            $watchStart = microtime(true);

            try {
                $outPut              = call_user_func($this->callBack, $this->params) ?? [];
                $this->retryAttempts = 0;
                $this->isFail        = false;
            } catch (Throwable $th) {
                $this->retryAttempts--;
                $this->isFail = true;
                $outPut       = ['error' => $th->getMessage()];
            }

            $watchEnd = round(microtime(true) - $watchStart, 3) * 1000;

            if (!$this->anonymously) {
                $this->logger?->interpolate(
                    $this->eventName,
                    [
                        'execute_time'  => $watchEnd,
                        'cron_time'     => $this->time,
                        'event_name'    => $this->eventName,
                        'attempts'      => $this->retryAttempts,
                        'error_message' => $outPut,
                    ]
                );
            }
        }
    }

    /**
     * Placeholder method for internal logging or message interpolation.
     *
     * @param string $message Message to log.
     * @param array<string, mixed> $context Context data for interpolation.
     * @return void
     */
    protected function interpolate(string $message, array $context): void
    {
        // do stuff
    }

    /**
     * Set the logger instance to be used for recording schedule execution.
     *
     * @param InterpolateInterface $logger Logger implementing InterpolateInterface.
     * @return void
     */
    public function setLogger(InterpolateInterface $logger): void
    {
        $this->logger = $logger;
    }

    /**
     * Check if the schedule is due to run at the current time.
     *
     * Compares the current timestamp against the defined time expectations.
     *
     * @return bool True if the schedule should run now.
     */
    public function isDue(): bool
    {
        $events     = $this->timeExpect;
        $dayLetter  = date('D', $this->time);
        $day        = date('d', $this->time);
        $hour       = date('H', $this->time);
        $minute     = date('i', $this->time);

        foreach ($events as $event) {
            $eventDayLetter = $event['D'] ?? $dayLetter;

            if (
                $eventDayLetter == $dayLetter
                && $event['d'] == $day
                && $event['h'] == $hour
                && $event['m'] == $minute
            ) {
                return true;
            }
        }

        return false;
    }

    /**
     * Schedule the task to run exactly at the current timestamp.
     *
     * Sets the timeName to 'justInTime' and updates the timeExpect accordingly.
     *
     * @return $this Fluent interface.
     */
    public function justInTime(): self
    {
        $this->timeName  = __FUNCTION__;
        $this->timeExpect = [
            [
                'D' => date('D', $this->time),
                'd' => date('d', $this->time),
                'h' => date('H', $this->time),
                'm' => date('i', $this->time),
            ],
        ];

        return $this;
    }

    /**
     * Schedule the task to run every 10 minutes within the hour.
     *
     * Sets the timeName to 'everyTenMinute' and updates the timeExpect array.
     *
     * @return $this Fluent interface.
     */
    public function everyTenMinute(): self
    {
        $this->timeName = __FUNCTION__;
        $minute = [];
        foreach (range(0, 59) as $time) {
            if ($time % 10 === 0) {
                $minute[] = [
                    'd' => date('d', $this->time),
                    'h' => date('H', $this->time),
                    'm' => $time,
                ];
            }
        }

        $this->timeExpect = $minute;

        return $this;
    }

    /**
     * Schedule the task to run every 30 minutes (on the hour and half hour).
     *
     * @return $this Fluent interface.
     */
    public function everyThirtyMinutes(): self
    {
        $this->timeName  = __FUNCTION__;
        $this->timeExpect = [
            ['d' => date('d', $this->time), 'h' => date('H', $this->time), 'm' => 0],
            ['d' => date('d', $this->time), 'h' => date('H', $this->time), 'm' => 30],
        ];

        return $this;
    }

    /**
     * Schedule the task to run every two hours.
     *
     * @return $this Fluent interface.
     */
    public function everyTwoHour(): self
    {
        $this->timeName = __FUNCTION__;
        $thisDay = date('d', $this->time);
        $hourly = [];
        foreach (range(0, 23) as $time) {
            if ($time % 2 === 0) {
                $hourly[] = ['d' => $thisDay, 'h' => $time, 'm' => 0];
            }
        }
        $this->timeExpect = $hourly;

        return $this;
    }

    /**
     * Schedule the task to run every 12 hours (midnight and noon).
     *
     * @return $this Fluent interface.
     */
    public function everyTwelveHour(): self
    {
        $this->timeName = __FUNCTION__;
        $this->timeExpect = [
            ['d' => date('d', $this->time), 'h' => 0, 'm' => 0],
            ['d' => date('d', $this->time), 'h' => 12, 'm' => 0],
        ];

        return $this;
    }

    /**
     * Schedule the task to run hourly on the hour.
     *
     * @return $this Fluent interface.
     */
    public function hourly(): self
    {
        $this->timeName = __FUNCTION__;
        $hourly = [];
        foreach (range(0, 23) as $time) {
            $hourly[] = ['d' => date('d', $this->time), 'h' => $time, 'm' => 0];
        }
        $this->timeExpect = $hourly;

        return $this;
    }

    /**
     * Schedule the task to run once at a specific hour of the day.
     *
     * @param int $hour24 Hour in 24-hour format.
     * @return $this Fluent interface.
     */
    public function hourlyAt(int $hour24): self
    {
        $this->timeName = __FUNCTION__;
        $this->timeExpect = [
            ['d' => date('d', $this->time), 'h' => $hour24, 'm' => 0],
        ];

        return $this;
    }

    /**
     * Schedule the task to run daily at midnight.
     *
     * @return $this Fluent interface.
     */
    public function daily(): self
    {
        $this->timeName = __FUNCTION__;
        $this->timeExpect = [['d' => (int) date('d'), 'h' => 0, 'm' => 0]];

        return $this;
    }

    /**
     * Schedule the task to run daily at a specific day of the month at midnight.
     *
     * @param int $day Day of the month.
     * @return $this Fluent interface.
     */
    public function dailyAt(int $day): self
    {
        $this->timeName = __FUNCTION__;
        $this->timeExpect = [['d' => $day, 'h' => 0, 'm' => 0]];

        return $this;
    }

    /**
     * Schedule the task to run weekly on Sunday at midnight.
     *
     * Sets the timeName to 'weekly' and the timeExpect to the next Sunday at 00:00.
     *
     * @return $this Fluent interface.
     */
    public function weekly(): self
    {
        $this->timeName  = __FUNCTION__;
        $this->timeExpect = [
            [
                'D' => 'Sun',
                'd' => date('d', $this->time),
                'h' => 0,
                'm' => 0,
            ],
        ];

        return $this;
    }

    /**
     * Schedule the task to run monthly on the first day at midnight.
     *
     * Sets the timeName to 'monthly' and the timeExpect to the 1st day of the month at 00:00.
     *
     * @return $this Fluent interface.
     */
    public function monthly(): self
    {
        $this->timeName  = __FUNCTION__;
        $this->timeExpect = [
            [
                'd' => 1,
                'h' => 0,
                'm' => 0,
            ],
        ];

        return $this;
    }
}
