<?php

declare(strict_types=1);

namespace Tests\Cron;

use Omega\Cron\InterpolateInterface;
use Omega\Cron\Schedule;
use Omega\Cron\ScheduleTime;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use ReflectionClass;
use Tests\TestCase;

#[CoversClass(Schedule::class)]
final class ScheduleCoverageTest extends TestCase
{
    /**
     * @return iterable<string, array{0: bool, 1: bool, 2: bool, 3: bool}>
     */
    public static function executePathProvider(): iterable
    {
        foreach ([false, true] as $withLogger) {
            foreach ([false, true] as $withTime) {
                foreach ([false, true] as $withLoop) {
                    foreach ([false, true] as $withRetry) {
                        yield sprintf(
                            'logger=%s time=%s loop=%s retry=%s',
                            $withLogger ? 'yes' : 'no',
                            $withTime ? 'yes' : 'no',
                            $withLoop ? 'twice' : 'once',
                            $withRetry ? 'yes' : 'no'
                        ) => [$withLogger, $withTime, $withLoop, $withRetry];
                    }
                }
            }
        }
    }

    #[DataProvider('executePathProvider')]
    public function testExecuteCoversEveryPath(
        bool $withLogger,
        bool $withTime,
        bool $withLoop,
        bool $withRetry
    ): void {
        $time   = 1600000000;
        $logger = $withLogger ? $this->createStub(InterpolateInterface::class) : null;
        $cron   = $this->createMock(ScheduleTime::class);

        $expects = ($withLoop ? 2 : 1) + ($withRetry ? 1 : 0);
        $cron->expects($this->exactly($expects))->method('expect');
        $cron->method('retryAttempts')->willReturnOnConsecutiveCalls(
            ...($withLoop ? [1, 0] : [0])
        );
        $cron->method('isRetry')->willReturn($withRetry);

        if ($withLogger) {
            $cron->expects($this->once())->method('setLogger')->with($logger);
        } else {
            $cron->expects($this->never())->method('setLogger');
        }

        if ($withTime) {
            $cron->expects($this->once())->method('setTime')->with($time);
        } else {
            $cron->expects($this->never())->method('setTime');
        }

        $schedule = new Schedule($withTime ? $time : null, $logger);
        $this->injectPools($schedule, [$cron]);

        $schedule->execute();

        $this->assertSame([$cron], $schedule->getPools());
    }

    public function testExecuteWithEmptyPool(): void
    {
        $schedule = new Schedule();

        $schedule->execute();

        $this->assertSame([], $schedule->getPools());
    }

    public function testSettersArePropagatedToTasks(): void
    {
        $time   = 1700000000;
        $logger = $this->createStub(InterpolateInterface::class);
        $cron   = $this->createMock(ScheduleTime::class);

        $cron->expects($this->once())->method('setLogger')->with($logger);
        $cron->expects($this->once())->method('setTime')->with($time);
        $cron->expects($this->once())->method('expect');
        $cron->method('retryAttempts')->willReturn(0);
        $cron->method('isRetry')->willReturn(false);

        $schedule = new Schedule();
        $schedule->setLogger($logger);
        $schedule->setTime($time);
        $this->injectPools($schedule, [$cron]);

        $schedule->execute();

        $this->assertSame([$cron], $schedule->getPools());
    }

    public function testAddMergesPools(): void
    {
        $target = new Schedule();
        $source = new Schedule();
        $task   = $this->createStub(ScheduleTime::class);
        $this->injectPools($source, [$task]);

        $this->assertSame($target, $target->add($source));
        $this->assertSame([$task], $target->getPools());
    }

    public function testAddWithEmptyPoolLeavesTargetUnchanged(): void
    {
        $target = new Schedule();
        $source = new Schedule();

        $this->assertSame($target, $target->add($source));
        $this->assertSame([], $target->getPools());
    }

    public function testFlushClearsPools(): void
    {
        $schedule = new Schedule();
        $this->injectPools($schedule, [$this->createStub(ScheduleTime::class)]);

        $schedule->flush();

        $this->assertSame([], $schedule->getPools());
    }

    /**
     * @param ScheduleTime[] $pools
     */
    private function injectPools(Schedule $schedule, array $pools): void
    {
        (new ReflectionClass(Schedule::class))
            ->getProperty('pools')
            ->setValue($schedule, $pools);
    }
}
