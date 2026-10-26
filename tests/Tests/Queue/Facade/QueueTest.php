<?php

declare(strict_types=1);

namespace Tests\Queue\Facade;

use Omega\Queue\Facade\Queue;
use PHPUnit\Framework\Attributes\CoversClass;
use Tests\TestCase;

#[CoversClass(Queue::class)]
final class QueueTest extends TestCase
{
    public function testExposesTheQueueAccessor(): void
    {
        $this->assertSame('queue', Queue::getFacadeAccessor());
    }
}
