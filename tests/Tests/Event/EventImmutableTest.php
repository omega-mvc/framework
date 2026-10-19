<?php

declare(strict_types=1);

namespace Tests\Event;

use Omega\Event\EventImmutable;
use Omega\Event\Exceptions\EventImmutableException;
use PHPUnit\Framework\Attributes\CoversClass;
use Tests\TestCase;

#[CoversClass(EventImmutable::class)]
#[CoversClass(EventImmutableException::class)]
final class EventImmutableTest extends TestCase
{
    public function testCreatesAnImmutableEventWithArguments(): void
    {
        $event = new EventImmutable('route.before', ['uri' => '/home', 'method' => 'GET']);

        $this->assertSame('route.before', $event->getName());
        $this->assertEquals(['uri' => '/home', 'method' => 'GET'], $event->getArguments());
        $this->assertSame('/home', $event->getArgument('uri'));
        $this->assertTrue($event->hasArgument('method'));
    }

    public function testRejectsSettingAnArgument(): void
    {
        $event = new EventImmutable('route.before', ['uri' => '/home']);

        $this->expectException(EventImmutableException::class);

        $event['uri'] = '/admin';
    }

    public function testRejectsRemovingAnArgument(): void
    {
        $event = new EventImmutable('route.before', ['uri' => '/home']);

        $this->expectException(EventImmutableException::class);

        unset($event['uri']);
    }

    public function testRejectsReconstructionOfTheImmutableEvent(): void
    {
        $event = new EventImmutable('route.before', ['uri' => '/home']);

        $this->expectException(EventImmutableException::class);

        $event->__construct('route.after');
    }
}
