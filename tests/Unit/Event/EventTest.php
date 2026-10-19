<?php

declare(strict_types=1);

namespace Tests\Event;

use Omega\Event\AbstractEvent;
use Omega\Event\Event;
use Omega\Event\Exceptions\InvalidEventArgumentNameException;
use PHPUnit\Framework\Attributes\CoversClass;
use Tests\TestCase;
use UnexpectedValueException;

use function count;
use function serialize;
use function unserialize;

#[CoversClass(AbstractEvent::class)]
#[CoversClass(Event::class)]
final class EventTest extends TestCase
{
    public function testReturnsTheEventName(): void
    {
        $event = new Event('order.created');

        $this->assertSame('order.created', $event->getName());
    }

    public function testReturnsAnArgumentWithItsValue(): void
    {
        $event = new Event('order.created', ['orderId' => 42]);

        $this->assertSame(42, $event->getArgument('orderId'));
    }

    public function testReturnsTheDefaultWhenAnArgumentIsMissing(): void
    {
        $event = new Event('order.created');

        $this->assertSame(0, $event->getArgument('orderId', 0));
        $this->assertNull($event->getArgument('orderId'));
    }

    public function testChecksWhetherAnArgumentExists(): void
    {
        $event = new Event('order.created', ['orderId' => 42]);

        $this->assertTrue($event->hasArgument('orderId'));
        $this->assertFalse($event->hasArgument('missing'));
    }

    public function testReturnsAllArguments(): void
    {
        $event = new Event('order.created', ['orderId' => 42]);

        $this->assertEquals(['orderId' => 42], $event->getArguments());
    }

    public function testKeepsAnExistingArgumentWhenAddingAgain(): void
    {
        $event = (new Event('order.created'))->addArgument('orderId', 1);

        $event->addArgument('orderId', 2);

        $this->assertSame(1, $event->getArgument('orderId'));
    }

    public function testOverridesAnArgumentWhenSettingAgain(): void
    {
        $event = (new Event('order.created'))->setArgument('orderId', 1);

        $event->setArgument('orderId', 2);

        $this->assertSame(2, $event->getArgument('orderId'));
    }

    public function testRemovesAnArgumentReturningItsPreviousValue(): void
    {
        $event = new Event('order.created', ['orderId' => 42, 'keep' => true]);

        $this->assertSame(42, $event->removeArgument('orderId'));
        $this->assertFalse($event->hasArgument('orderId'));
        $this->assertNull($event->removeArgument('orderId'));
        $this->assertTrue($event->getArgument('keep'));
    }

    public function testClearsAllArgumentsReturningThePreviousSet(): void
    {
        $event = new Event('order.created', ['orderId' => 42]);

        $this->assertEquals(['orderId' => 42], $event->clearArguments());
        $this->assertEmpty($event->getArguments());
    }

    public function testCountsItsArguments(): void
    {
        $event = new Event('order.created', ['orderId' => 42, 'customer' => 'morpheus']);

        $this->assertSame(2, count($event));
    }

    public function testExposesArgumentsThroughArrayAccess(): void
    {
        $event = new Event('order.created');

        $event['orderId'] = 42;

        $this->assertTrue(isset($event['orderId']));
        $this->assertSame(42, $event['orderId']);

        unset($event['orderId']);

        $this->assertFalse(isset($event['orderId']));
        $this->assertNull($event['orderId']);
    }

    public function testRejectsANullArgumentNameInArrayAccess(): void
    {
        $event = new Event('order.created');

        $this->expectException(InvalidEventArgumentNameException::class);

        $event->offsetSet(null, 42);
    }

    public function testRejectsAnIntegerArgumentNameInArrayAccess(): void
    {
        $event = new Event('order.created');

        $this->expectException(InvalidEventArgumentNameException::class);

        $event->offsetSet(1, 42);
    }

    public function testReturnsNullWhenReadingAnIntegerOffset(): void
    {
        $event = new Event('order.created', ['orderId' => 42]);

        $this->assertNull($event->offsetGet(0));
        $this->assertFalse($event->offsetExists(0));
    }

    public function testControlsPropagation(): void
    {
        $event = new Event('order.created');

        $this->assertFalse($event->isStopped());

        $event->stopPropagation();

        $this->assertTrue($event->isStopped());
    }

    public function testSurvivesASerializationRoundtrip(): void
    {
        $event    = new Event('order.created', ['orderId' => 42]);
        $restored = unserialize(serialize($event));

        $this->assertInstanceOf(Event::class, $restored);
        $this->assertSame('order.created', $restored->getName());
        $this->assertEquals(['orderId' => 42], $restored->getArguments());
        $this->assertFalse($restored->isStopped());
    }

    public function testRestoresTheStoppedFlagAfterSerialization(): void
    {
        $event = new Event('order.created');
        $event->stopPropagation();

        $restored = unserialize(serialize($event));

        $this->assertInstanceOf(Event::class, $restored);
        $this->assertTrue($restored->isStopped());
    }

    public function testSupportsTheLegacySerializeRoundtrip(): void
    {
        $event    = new Event('order.created', ['orderId' => 42]);
        $restored = new Event('temporary');
        $restored->unserialize($event->serialize());

        $this->assertSame('order.created', $restored->getName());
        $this->assertEquals(['orderId' => 42], $restored->getArguments());
    }

    public function testRejectsANonArrayPayload(): void
    {
        $event = new Event('temporary');

        $this->expectException(UnexpectedValueException::class);

        $event->unserialize(serialize(42));
    }

    public function testRejectsAMalformedSerializedEvent(): void
    {
        $event = new Event('temporary');

        $this->expectException(UnexpectedValueException::class);

        $event->unserialize(serialize(['name' => 5, 'arguments' => [], 'stopped' => false]));
    }

    public function testRejectsANonStringArgumentKeyDuringUnserialization(): void
    {
        $event = new Event('temporary');

        $this->expectException(UnexpectedValueException::class);

        $event->__unserialize(['name' => 'order.created', 'arguments' => [1 => 'value'], 'stopped' => false]);
    }

    public function testRejectsNonArrayArgumentsDuringUnserialization(): void
    {
        $event = new Event('temporary');

        $this->expectException(UnexpectedValueException::class);

        $event->__unserialize(['name' => 'order.created', 'arguments' => 'invalid', 'stopped' => false]);
    }

    public function testRejectsANonBooleanStoppedFlagDuringUnserialization(): void
    {
        $event = new Event('temporary');

        $this->expectException(UnexpectedValueException::class);

        $event->__unserialize(['name' => 'order.created', 'arguments' => [], 'stopped' => 1]);
    }

    public function testIgnoresNonStringOffsetsWhenUnsettingArguments(): void
    {
        $event = new Event('temporary', ['a' => 1]);

        unset($event[5]);

        $this->assertTrue($event->hasArgument('a'));
    }
}
