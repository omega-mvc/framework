<?php

declare(strict_types=1);

namespace Tests\Event;

use Omega\Event\Priority;
use PHPUnit\Framework\Attributes\CoversClass;
use Tests\TestCase;

use function array_map;

#[CoversClass(Priority::class)]
final class PriorityTest extends TestCase
{
    public function testExposesTheMinimumPriority(): void
    {
        $this->assertSame(-3, Priority::MIN->value);
    }

    public function testExposesALowPriority(): void
    {
        $this->assertSame(-2, Priority::LOW->value);
    }

    public function testExposesABelowNormalPriority(): void
    {
        $this->assertSame(-1, Priority::BELOW_NORMAL->value);
    }

    public function testExposesTheNormalPriority(): void
    {
        $this->assertSame(0, Priority::NORMAL->value);
    }

    public function testExposesAnAboveNormalPriority(): void
    {
        $this->assertSame(1, Priority::ABOVE_NORMAL->value);
    }

    public function testExposesAHighPriority(): void
    {
        $this->assertSame(2, Priority::HIGH->value);
    }

    public function testExposesTheMaximumPriority(): void
    {
        $this->assertSame(3, Priority::MAX->value);
    }

    public function testOrdersCasesInAscendingPriority(): void
    {
        $values = array_map(static fn (Priority $priority): int => $priority->value, Priority::cases());

        $this->assertEquals([-3, -2, -1, 0, 1, 2, 3], $values);
    }
}
