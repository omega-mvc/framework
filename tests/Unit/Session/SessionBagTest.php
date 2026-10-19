<?php

declare(strict_types=1);

namespace Tests\Session;

use Omega\Session\SessionBag;
use PHPUnit\Framework\Attributes\CoversClass;
use Tests\TestCase;

#[CoversClass(SessionBag::class)]
final class SessionBagTest extends TestCase
{
    public function testReturnsTheBagName(): void
    {
        $data = [];
        $bag  = new SessionBag('cart', $data);

        $this->assertSame('cart', $bag->getName());
    }

    public function testStoresReadsAndChecksValuesWithADefault(): void
    {
        $data = [];
        $bag  = new SessionBag('cart', $data);

        $this->assertFalse($bag->has('item'));

        $bag->put('item', 'sku-1');

        $this->assertTrue($bag->has('item'));
        $this->assertSame('sku-1', $bag->get('item'));
        $this->assertSame('fallback', $bag->get('missing', 'fallback'));
    }

    public function testMutatesTheReferencedDataArray(): void
    {
        $data = [];
        $bag  = new SessionBag('cart', $data);

        $bag->put('item', 'sku-1');

        $this->assertEquals(['item' => 'sku-1'], $data);
    }

    public function testForgetsAValue(): void
    {
        $data = ['item' => 'sku-1', 'qty' => 2];
        $bag  = new SessionBag('cart', $data);

        $bag->forget('item');

        $this->assertFalse($bag->has('item'));
        $this->assertTrue($bag->has('qty'));
        $this->assertEquals(['qty' => 2], $data);
    }

    public function testFlushesAllValues(): void
    {
        $data = ['item' => 'sku-1'];
        $bag  = new SessionBag('cart', $data);

        $bag->flush();

        $this->assertEmpty($bag->all());
        $this->assertEmpty($data);
    }

    public function testReturnsAllValues(): void
    {
        $data = ['a' => 1, 'b' => 2];
        $bag  = new SessionBag('cart', $data);

        $this->assertEquals(['a' => 1, 'b' => 2], $bag->all());
    }
}
