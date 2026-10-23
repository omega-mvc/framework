<?php

declare(strict_types=1);

namespace Tests\Event;

use Omega\Event\Dispatcher\Dispatcher;
use Omega\Event\Dispatcher\DispatcherAwareTrait;
use Omega\Event\Exceptions\DispatcherNotSetException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversTrait;
use Tests\Event\Support\DispatcherAwareService;
use Tests\TestCase;

#[CoversTrait(DispatcherAwareTrait::class)]
#[CoversClass(DispatcherNotSetException::class)]
final class DispatcherAwareTest extends TestCase
{
    public function testThrowsWhenNoDispatcherHasBeenSet(): void
    {
        $service = new DispatcherAwareService();

        $this->expectException(DispatcherNotSetException::class);

        $service->getDispatcher();
    }

    public function testSetsAndReturnsTheDispatcher(): void
    {
        $service    = new DispatcherAwareService();
        $dispatcher = new Dispatcher();

        $this->assertSame($service, $service->setDispatcher($dispatcher));
        $this->assertSame($dispatcher, $service->getDispatcher());
    }

    public function testReturnsTheMostRecentDispatcher(): void
    {
        $service = new DispatcherAwareService();
        $first   = new Dispatcher();
        $second  = new Dispatcher();

        $service->setDispatcher($first);
        $service->setDispatcher($second);

        $this->assertSame($second, $service->getDispatcher());
    }
}
