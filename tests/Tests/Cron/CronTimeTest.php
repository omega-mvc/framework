<?php

/**
 * Part of Omega - Tests\Cron Package.
 *
 * @link      https://omega-mvc.github.io
 * @author    Adriano Giovannini <agisoftt@gmail.com>
 * @copyright Copyright (c) 2025 - 2026 Adriano Giovannini (https://omega-mvc.github.io)
 * @license   https://www.gnu.org/licenses/gpl-3.0-standalone.html     GPL V3.0+
 * @version   2.0.0
 */

declare(strict_types=1);

namespace Tests\Cron;

use DateInvalidTimeZoneException;
use DateMalformedStringException;
use Omega\Cron\InterpolateInterface;
use Omega\Cron\Schedule;
use Omega\Cron\ScheduleTime;
use Omega\Time\Now;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

use function Omega\Time\now;

/**
 * Class CronTimeTest
 *
 * This test suite verifies that the scheduling system executes tasks at the correct time intervals.
 * It ensures that the `Schedule` and `ScheduleTime` classes properly recognize when a scheduled task
 * is "due" based on different time configurations such as:
 *
 * - Just-in-time execution
 * - Every N minutes/hours (e.g., every ten or thirty minutes, every two or twelve hours)
 * - Hourly, daily, weekly, and monthly schedules
 * - Specific time-based variants (e.g., `hourlyAt`, `dailyAt`)
 *
 * Each test simulates a controlled time context using the `Now` class, validates the expected
 * behavior of `isDue()` for scheduled tasks, and ensures reliable and predictable scheduling
 * operations without relying on real system time.
 *
 * @category  Tests
 * @package   Cron
 * @link      https://omega-mvc.github.io
 * @author    Adriano Giovannini <agisoftt@gmail.com>
 * @copyright Copyright (c) 2025 - 2026 Adriano Giovannini (https://omega-mvc.github.io)
 * @license   https://www.gnu.org/licenses/gpl-3.0-standalone.html     GPL V3.0+
 * @version   2.0.0
 */
#[CoversClass(Schedule::class)]
#[CoversClass(ScheduleTime::class)]
#[CoversClass(Now::class)]
final class CronTimeTest extends TestCase
{
    /**
     * Logger instance used to handle interpolated debug output during schedule execution.
     *
     * This is a nullable implementation of `InterpolateInterface`, injected in `setUp()`
     * to avoid external dependencies. The logger does not write to files or streams; it
     * simply simulates logging so the tests can verify behavior without performing I/O.
     *
     * @var InterpolateInterface|null
     */
    private ?InterpolateInterface $logger;

    /**
     * Sets up the environment before each test method.
     *
     * This method is called automatically by PHPUnit before each test runs.
     * It is responsible for initializing the application instance, setting up
     * dependencies, and preparing any state required by the test.
     *
     * @return void
     */
    protected function setUp(): void
    {
        $this->logger = new class implements InterpolateInterface {
            public function interpolate(string $message, array $context = []): void
            {
                echo 'works';
            }
        };
    }

    /**
     * Tears down the environment after each test method.
     *
     * This method is called automatically by PHPUnit after each test runs.
     * It is responsible for cleaning up resources, flushing the application
     * state, unsetting properties, and resetting any static or global state
     * to avoid side effects between tests.
     *
     * @return void
     */
    protected function tearDown(): void
    {
        $this->logger = null;
    }

    /**
     * Test it run only just in time.
     *
     * @return void
     * @throws DateInvalidTimeZoneException Thrown when a provided timezone is invalid.
     * @throws DateMalformedStringException Thrown when a date string cannot be parsed correctly.
     */
    public function testItRunOnlyJustInTime(): void
    {
        $anonymously = new Schedule(now()->timestamp, $this->logger);
        $anonymously
            ->call(fn (): string => 'due time')
            ->justInTime()
            ->eventName('test 01');

        foreach ($anonymously->getPools() as $scheduleItem) {
            if ($scheduleItem instanceof ScheduleTime) {
                $this->assertTrue($scheduleItem->isDue());
            }
        }
    }

    /**
     * Test it run only every ten minute.
     *
     * @return void
     */
    public function testItRunOnlyEveryTenMinute(): void
    {
        $timeTravel  = new Now('09/07/2021 00:00:00');
        $anonymously = new Schedule($timeTravel->timestamp, $this->logger);
        $anonymously
            ->call(fn (): string => 'due time')
            ->everyTenMinute()
            ->eventName('test 10 minute');

        foreach ($anonymously->getPools() as $scheduleItem) {
            if ($scheduleItem instanceof ScheduleTime) {
                $this->assertTrue($scheduleItem->isDue());
            }
        }
    }

    /**
     * Test it run only every thirty minutes.
     *
     * @return void
     */
    public function testItRunOnlyEveryThirtyMinutes(): void
    {
        $timeTravel  = new Now('09/07/2021 00:30:00');
        $anonymously = new Schedule($timeTravel->timestamp, $this->logger);
        $anonymously
            ->call(fn (): string => 'due time')
            ->everyThirtyMinutes()
            ->eventName('test 30 minute');

        foreach ($anonymously->getPools() as $scheduleItem) {
            if ($scheduleItem instanceof ScheduleTime) {
                $this->assertTrue($scheduleItem->isDue());
            }
        }
    }

    /**
     * Test it run only every two hour.
     *
     * @return void
     */
    public function testItRunOnlyEveryTwoHour(): void
    {
        $timeTravel  = new Now('09/07/2021 02:00:00');
        $anonymously = new Schedule($timeTravel->timestamp, $this->logger);
        $anonymously
            ->call(fn (): string => 'due time')
            ->everyTwoHour()
            ->eventName('test 2 hour');

        foreach ($anonymously->getPools() as $scheduleItem) {
            if ($scheduleItem instanceof ScheduleTime) {
                $this->assertTrue($scheduleItem->isDue());
            }
        }
    }

    /**
     * Test it run only every twelve hour.
     *
     * @return void
     */
    public function testItRunOnlyEveryTwelveHour(): void
    {
        $timeTravel  = new Now('09/07/2021 12:00:00');
        $anonymously = new Schedule($timeTravel->timestamp, $this->logger);
        $anonymously
            ->call(fn (): string => 'due time')
            ->everyTwelveHour()
            ->eventName('test 12 hour');

        foreach ($anonymously->getPools() as $scheduleItem) {
            if ($scheduleItem instanceof ScheduleTime) {
                $this->assertTrue($scheduleItem->isDue());
            }
        }
    }

    /**
     * Test it run only hourly.
     *
     * @return void
     */
    public function testItRunOnlyHourly(): void
    {
        $timeTravel  = new Now('09/07/2021 00:00:00');
        $anonymously = new Schedule($timeTravel->timestamp, $this->logger);
        $anonymously
            ->call(fn (): string => 'due time')
            ->hourly()
            ->eventName('test hourly');

        foreach ($anonymously->getPools() as $scheduleItem) {
            if ($scheduleItem instanceof ScheduleTime) {
                $this->assertTrue($scheduleItem->isDue());
            }
        }
    }

    /**
     * Test it run only hourly at.
     *
     * @return void
     */
    public function testItRunOnlyHourlyAt(): void
    {
        $timeTravel  = new Now('09/07/2021 05:00:00');
        $anonymously = new Schedule($timeTravel->timestamp, $this->logger);
        $anonymously
            ->call(fn (): string => 'due time')
            ->hourlyAt(5)
            ->eventName('test hourlyAt 5 hour');

        foreach ($anonymously->getPools() as $scheduleItem) {
            if ($scheduleItem instanceof ScheduleTime) {
                $this->assertTrue($scheduleItem->isDue());
            }
        }
    }

    /**
     * Test it run only daily.
     *
     * @return void
     */
    public function testItRunOnlyDaily(): void
    {
        $timeTravel  = new Now('00:00:00');
        $anonymously = new Schedule($timeTravel->timestamp, $this->logger);
        $anonymously
            ->call(fn (): string => 'due time')
            ->daily()
            ->eventName('test daily');

        foreach ($anonymously->getPools() as $scheduleItem) {
            if ($scheduleItem instanceof ScheduleTime) {
                // die(var_dump($scheduleItem->getTimeExpect()));
                $this->assertTrue($scheduleItem->isDue());
            }
        }
    }

    /**
     * Test it run only daily at.
     *
     * @return void
     */
    public function testItRunOnlyDailyAt(): void
    {
        $timeTravel  = new Now('12/12/2012 00:00:00');
        $anonymously = new Schedule($timeTravel->timestamp, $this->logger);
        $anonymously
            ->call(fn (): string => 'due time')
            ->dailyAt(12)
            ->eventName('test dailyAt 12');

        foreach ($anonymously->getPools() as $scheduleItem) {
            if ($scheduleItem instanceof ScheduleTime) {
                $this->assertTrue($scheduleItem->isDue());
            }
        }
    }

    /**
     * Test it run only weekly.
     *
     * @return void
     */
    public function testItRunOnlyWeekly(): void
    {
        $timeTravel  = new Now('12/16/2012 00:00:00');
        $anonymously = new Schedule($timeTravel->timestamp, $this->logger);
        $anonymously
            ->call(fn (): string => 'due time')
            ->weekly()
            ->eventName('test weekly');

        foreach ($anonymously->getPools() as $scheduleItem) {
            if ($scheduleItem instanceof ScheduleTime) {
                // die(var_dump($scheduleItem->getTimeExpect()));
                $this->assertTrue($scheduleItem->isDue());
            }
        }
    }

    /**
     * Test it run only monthly.
     *
     * @return void
     */
    public function testItRunOnlyMonthly(): void
    {
        $timeTravel  = new Now('1/1/2012 00:00:00');
        $anonymously = new Schedule($timeTravel->timestamp, $this->logger);
        $anonymously
            ->call(fn (): string => 'due time')
            ->monthly()
            ->eventName('test monthly');

        foreach ($anonymously->getPools() as $scheduleItem) {
            if ($scheduleItem instanceof ScheduleTime) {
                $this->assertTrue($scheduleItem->isDue());
            }
        }
    }
}
