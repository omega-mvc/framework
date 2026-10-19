<?php

declare(strict_types=1);

namespace Tests\Event;

use Omega\Event\Events\ExceptionEvent;
use Omega\Event\Events\LogEvent;
use Omega\Event\Events\ModelEvent;
use Omega\Event\Events\RouteEvent;
use PHPUnit\Framework\Attributes\CoversClass;
use RuntimeException;
use Tests\Event\Support\FakeConnection;
use Tests\Event\Support\StubModel;
use Tests\TestCase;

#[CoversClass(RouteEvent::class)]
#[CoversClass(LogEvent::class)]
#[CoversClass(ExceptionEvent::class)]
#[CoversClass(ModelEvent::class)]
final class EventsTest extends TestCase
{
    public function testCreatesARouteBeforeEvent(): void
    {
        $event = RouteEvent::before('/home', 'GET');

        $this->assertSame('route.before', $event->getName());
        $this->assertSame('/home', $event->getArgument('uri'));
        $this->assertSame('GET', $event->getArgument('method'));
    }

    public function testCreatesARouteAfterEvent(): void
    {
        $event = RouteEvent::after('/home', 'GET', fn (): string => 'ok', ['id' => 1]);

        $this->assertSame('route.after', $event->getName());
        $this->assertSame('/home', $event->getArgument('uri'));
        $this->assertSame('GET', $event->getArgument('method'));
        $this->assertIsCallable($event->getArgument('callable'));
        $this->assertEquals(['id' => 1], $event->getArgument('parameters'));
    }

    public function testCreatesALogWrittenEvent(): void
    {
        $event = LogEvent::written('info', 'An event happened', ['user' => 1]);

        $this->assertSame('log.written', $event->getName());
        $this->assertSame('info', $event->getArgument('level'));
        $this->assertSame('An event happened', $event->getArgument('message'));
        $this->assertEquals(['user' => 1], $event->getArgument('context'));
    }

    public function testCreatesAnExceptionLoggedEvent(): void
    {
        $exception = new RuntimeException('boom');
        $event     = ExceptionEvent::create($exception, 'critical');

        $this->assertSame('exception.logged', $event->getName());
        $this->assertSame($exception, $event->getArgument('exception'));
        $this->assertSame('critical', $event->getArgument('level'));
        $this->assertSame('boom', $event->getArgument('message'));
        $this->assertSame(RuntimeException::class, $event->getArgument('class'));
    }

    public function testCreatesAModelCreatedEvent(): void
    {
        $model = new StubModel(new FakeConnection(), [['id' => 1]], 'stub_table');
        $event = ModelEvent::created($model);

        $this->assertSame('model.created', $event->getName());
        $this->assertSame($model, $event->getArgument('model'));
        $this->assertSame('stub_table', $event->getArgument('table'));
    }

    public function testCreatesAModelSavedEvent(): void
    {
        $model = new StubModel(new FakeConnection(), [['id' => 1]], 'stub_table');
        $event = ModelEvent::saved($model, true);

        $this->assertSame('model.saved', $event->getName());
        $this->assertSame($model, $event->getArgument('model'));
        $this->assertSame('stub_table', $event->getArgument('table'));
        $this->assertTrue($event->getArgument('created'));
    }

    public function testCreatesAModelDeletedEvent(): void
    {
        $model = new StubModel(new FakeConnection(), [['id' => 1]], 'stub_table');
        $event = ModelEvent::deleted($model);

        $this->assertSame('model.deleted', $event->getName());
        $this->assertSame($model, $event->getArgument('model'));
        $this->assertSame('stub_table', $event->getArgument('table'));
    }
}
