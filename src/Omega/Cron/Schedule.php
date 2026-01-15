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

/**
 * Class Schedule
 *
 * Manages a collection of scheduled tasks (`ScheduleTime`) and allows their execution
 * based on defined intervals, just-in-time triggers, and retry policies.
 *
 * Each `Schedule` object maintains a pool of scheduled tasks, which can be executed,
 * retried, or cleared. Logging can be optionally injected to monitor execution details.
 *
 * @category  Omega
 * @package   Cron
 * @link      https://omega-mvc.github.io
 * @author    Adriano Giovannini <agisoftt@gmail.com>
 * @copyright Copyright (c) 2025 - 2026 Adriano Giovannini (https://omega-mvc.github.io)
 * @license   https://www.gnu.org/licenses/gpl-3.0-standalone.html     GPL V3.0+
 * @version   2.0.0
 */
class Schedule
{
    /** @var ScheduleTime[] The collection of scheduled tasks (`ScheduleTime` instances) in this schedule.
     * @noinspection PhpGetterAndSetterCanBeReplacedWithPropertyHooksInspection
     */
    protected array $pools = [];

    /**
     * Schedule constructor.
     *
     * @param int|null $time Optional timestamp for the schedule. Defaults to current time.
     * @param InterpolateInterface|null $logger Optional logger to record task execution.
     */
    public function __construct(
        protected ?int $time = null,
        private ?InterpolateInterface $logger = null
    ) {
    }

    /**
     * Get all scheduled tasks in the pool.
     *
     * @return ScheduleTime[] Array of ScheduleTime objects.
     */
    public function getPools(): array
    {
        return $this->pools;
    }

    /**
     * Add a new task to the schedule pool.
     *
     * @param Closure $callBack The function to execute for this task.
     * @param array $params Optional parameters to pass to the callback.
     * @return ScheduleTime The created ScheduleTime instance.
     */
    public function call(Closure $callBack, array $params = []): ScheduleTime
    {
        return $this->pools[] = new ScheduleTime($callBack, $params, $this->time);
    }

    /**
     * Execute all tasks in the schedule pool.
     *
     * Each task will be executed according to its timing, retry policy, and
     * logging configuration.
     *
     * @return void
     */
    public function execute(): void
    {
        foreach ($this->pools as $cron) {
            $cron->setLogger($this->logger);
            do {
                $cron->expect();
            } while ($cron->retryAttempts() > 0);

            if ($cron->isRetry()) {
                $cron->expect();
            }
        }
    }

    /**
     * Inject a logger to record schedule execution.
     *
     * @param InterpolateInterface $logger The logger instance.
     * @return void
     */
    public function setLogger(InterpolateInterface $logger): void
    {
        $this->logger = $logger;
    }

    /**
     * Set the base timestamp for scheduling tasks.
     *
     * @param int $time Timestamp to set for the schedule.
     * @return void
     */
    public function setTime(int $time): void
    {
        $this->time = $time;
    }

    /**
     * Add another schedule's pool into this schedule.
     *
     * @param Schedule $schedule Another schedule instance.
     * @return self
     */
    public function add(Schedule $schedule): self
    {
        foreach ($schedule->getPools() as $time) {
            $this->pools[] = $time;
        }

        return $this;
    }

    /**
     * Clear all tasks from the schedule pool.
     *
     * @return void
     * @property ScheduleTime[] $pools Reset to an empty array.
     */
    public function flush(): void
    {
        $this->pools = [];
    }
}
