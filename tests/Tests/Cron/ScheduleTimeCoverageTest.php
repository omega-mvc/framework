<?php

declare(strict_types=1);

namespace Tests\Cron;

use Omega\Cron\InterpolateInterface;
use Omega\Cron\ScheduleTime;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use ReflectionClass;
use RuntimeException;
use Tests\TestCase;

use function date;

#[CoversClass(ScheduleTime::class)]
final class ScheduleTimeCoverageTest extends TestCase
{
    private const int TIME = 1625790600;

    /**
     * @return iterable<string, array{0: bool, 1: bool, 2: bool, 3: bool, 4: bool|null}>
     */
    public static function expectPathProvider(): iterable
    {
        yield 'due success logged' => [true, false, false, true, true];
        yield 'due success anonymous' => [true, true, false, true, false];
        yield 'due skipped' => [true, false, true, true, true];
        yield 'not due logged' => [true, false, false, false, true];
        yield 'not due anonymous' => [true, false, false, false, false];
        yield 'due failure logged' => [false, false, false, true, true];
        yield 'due failure anonymous' => [false, true, false, true, false];
        yield 'due success no logger' => [true, false, false, true, null];
        yield 'due failure no logger' => [false, false, false, true, null];
        yield 'due skipped failure' => [false, false, true, true, true];
        yield 'not due skipped' => [true, false, true, false, true];
    }

    /**
     * @param bool $success
     * @param bool $anonymous
     * @param bool $skip
     * @param bool $due
     * @param bool|null $withLogger
     */
    #[DataProvider('expectPathProvider')]
    public function testExpectCoversEveryPath(
        bool $success,
        bool $anonymous,
        bool $skip,
        bool $due,
        ?bool $withLogger
    ): void {
        $calls = 0;
        $task  = new ScheduleTime(
            static function () use (&$calls, $success): void {
                $calls++;

                if (!$success) {
                    throw new RuntimeException('failure');
                }
            },
            [],
            self::TIME
        );

        $task->justInTime();
        $task->skip($skip);

        if ($anonymous) {
            $task->anonymously();
        }

        if (!$due) {
            $task->setTime(self::TIME + 60);
        }

        $logger = null;

        if (null !== $withLogger) {
            $logger = $this->logger();
            $task->setLogger($logger);
        }

        $task->expect();

        if ($due && !$skip) {
            $this->assertSame(1, $calls);
        } else {
            $this->assertSame(0, $calls);
        }

        if (null !== $logger) {
            $this->assertSame($due && !$skip && !$anonymous ? 1 : 0, $logger->calls);
        }

        $this->assertSame($due && !$skip && !$success ? -1 : 0, $task->retryAttempts());
    }

    public function testSkipAcceptsBooleanAndClosure(): void
    {
        $task = $this->task();

        $this->assertSame($task, $task->skip(true));
        $this->assertSame($task, $task->skip(false));
        $this->assertSame($task, $task->skip(static fn (): bool => true));
    }

    /**
     * @return iterable<string, array{0: array<int, array<string, string>>, 1: bool}>
     */
    public static function isDuePathProvider(): iterable
    {
        $dayLetter = date('D', self::TIME);
        $day       = date('d', self::TIME);
        $hour      = date('H', self::TIME);
        $minute    = date('i', self::TIME);

        $base      = ['D' => $dayLetter, 'd' => $day, 'h' => $hour, 'm' => $minute];
        $altDay    = $day === '01' ? '02' : '01';
        $altHour   = $hour === '00' ? '01' : '00';
        $altMinute = $minute === '00' ? '01' : '00';

        $letterMismatch = ['D' => 'Xxx'] + $base;
        $dayMismatch    = ['d' => $altDay] + $base;
        $hourMismatch   = ['h' => $altHour] + $base;
        $minuteMismatch = ['m' => $altMinute] + $base;

        $noLetter       = ['d' => $day, 'h' => $hour, 'm' => $minute];
        $noLetterDay    = ['d' => $altDay, 'h' => $hour, 'm' => $minute];
        $noLetterHour   = ['d' => $day, 'h' => $altHour, 'm' => $minute];
        $noLetterMinute = ['d' => $day, 'h' => $hour, 'm' => $altMinute];
        $allMismatch    = ['D' => 'Xxx', 'd' => $altDay, 'h' => $altHour, 'm' => $altMinute];

        yield 'all match' => [[$base], true];
        yield 'day letter mismatch' => [[$letterMismatch], false];
        yield 'day mismatch' => [[$dayMismatch], false];
        yield 'hour mismatch' => [[$hourMismatch], false];
        yield 'minute mismatch' => [[$minuteMismatch], false];
        yield 'second event matches' => [[$letterMismatch, $base], true];
        yield 'no events' => [[], false];
        yield 'day letter absent' => [[$noLetter], true];
        yield 'day letter absent mismatch' => [[$noLetterDay], false];

        yield 'match then day letter mismatch' => [[$base, $letterMismatch], true];
        yield 'day letter mismatch then match' => [[$letterMismatch, $base], true];
        yield 'day mismatch then match' => [[$dayMismatch, $base], true];
        yield 'hour mismatch then match' => [[$hourMismatch, $base], true];
        yield 'minute mismatch then match' => [[$minuteMismatch, $base], true];
        yield 'day letter absent day mismatch then match' => [[$noLetterDay, $base], true];
        yield 'day letter absent hour mismatch then match' => [[$noLetterHour, $base], true];
        yield 'day letter absent minute mismatch then match' => [[$noLetterMinute, $base], true];
        yield 'two mismatches then match' => [[$letterMismatch, $dayMismatch, $base], true];
        yield 'three mismatches then match' => [[$letterMismatch, $dayMismatch, $hourMismatch, $base], true];
        yield 'all stages mismatch' => [[$allMismatch], false];
    }

    /**
     * @param array<int, array<string, string>> $events
     */
    #[DataProvider('isDuePathProvider')]
    public function testIsDueCoversEveryPath(array $events, bool $expected): void
    {
        $task = $this->task();
        $this->setExpectations($task, $events);

        $this->assertSame($expected, $task->isDue());
    }

    public function testEveryTenMinuteBuildsTenMinuteGrid(): void
    {
        $task = $this->task()->everyTenMinute();
        $grid = $this->expectations($task);

        $this->assertSame('everyTenMinute', $task->timeName);
        $this->assertCount(6, $grid);
        $this->assertSame([0, 10, 20, 30, 40, 50], array_column($grid, 'm'));
        $this->assertSame(array_fill(0, 6, date('d', self::TIME)), array_column($grid, 'd'));
        $this->assertSame(array_fill(0, 6, date('H', self::TIME)), array_column($grid, 'h'));
    }

    public function testEveryTwoHourBuildsTwoHourGrid(): void
    {
        $task = $this->task()->everyTwoHour();
        $grid = $this->expectations($task);

        $this->assertSame('everyTwoHour', $task->timeName);
        $this->assertCount(12, $grid);
        $this->assertSame([0, 2, 4, 6, 8, 10, 12, 14, 16, 18, 20, 22], array_column($grid, 'h'));
        $this->assertSame(array_fill(0, 12, 0), array_column($grid, 'm'));
    }

    private function task(): ScheduleTime
    {
        return new ScheduleTime(static fn (): bool => true, [], self::TIME);
    }

    /**
     * @return InterpolateInterface&object{calls: int}
     */
    private function logger(): InterpolateInterface
    {
        return new class implements InterpolateInterface {
            public int $calls = 0;

            public function interpolate(string $message, array $context = []): void
            {
                $this->calls++;
            }
        };
    }

    /**
     * @return array<int, array<string, int|string>>
     */
    private function expectations(ScheduleTime $task): array
    {
        /** @var array<int, array<string, int|string>> $expectations */
        $expectations = (new ReflectionClass(ScheduleTime::class))
            ->getProperty('timeExpect')
            ->getValue($task);

        return $expectations;
    }

    /**
     * @param array<int, array<string, string>> $events
     */
    private function setExpectations(ScheduleTime $task, array $events): void
    {
        (new ReflectionClass(ScheduleTime::class))
            ->getProperty('timeExpect')
            ->setValue($task, $events);
    }
}
