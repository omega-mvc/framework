<?php

declare(strict_types=1);

namespace Tests\Cron\Facade;

use Omega\Cron\Facade\Schedule;
use PHPUnit\Framework\Attributes\CoversClass;
use Tests\TestCase;

#[CoversClass(Schedule::class)]
final class ScheduleTest extends TestCase
{
    public function testReturnsTheScheduleFacadeAccessor(): void
    {
        $this->assertSame('schedule', Schedule::getFacadeAccessor());
    }
}
