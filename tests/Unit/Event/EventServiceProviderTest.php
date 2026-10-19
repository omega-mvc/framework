<?php

declare(strict_types=1);

namespace Tests\Event;

use Omega\Application\Application;
use Omega\Database\Model\Model;
use Omega\Event\Dispatcher\Dispatcher;
use Omega\Event\Dispatcher\DispatcherInterface;
use Omega\Event\EventServiceProvider;
use PHPUnit\Framework\Attributes\CoversClass;
use Tests\TestCase;

#[CoversClass(EventServiceProvider::class)]
final class EventServiceProviderTest extends TestCase
{
    public function testBootsAndRegistersTheDefaultModelDispatcher(): void
    {
        $app      = new Application(__DIR__);
        $provider = new EventServiceProvider($app);

        $provider->boot();

        $dispatcher = $app->make(DispatcherInterface::class);

        $this->assertInstanceOf(Dispatcher::class, $dispatcher);
        $this->assertSame($dispatcher, Model::getEventDispatcher());
    }

    public function testBootsAndRegistersTheEventsAlias(): void
    {
        $app      = new Application(__DIR__);
        $provider = new EventServiceProvider($app);

        $provider->boot();

        $this->assertInstanceOf(DispatcherInterface::class, $app->make('events'));
    }
}
