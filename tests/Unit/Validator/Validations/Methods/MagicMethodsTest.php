<?php

declare(strict_types=1);

namespace Tests\Validator\Validations\Methods;

use Tests\TestCase;

use function Omega\Validator\vr;

final class MagicMethodsTest extends TestCase
{
    public function testCanAccessUnknownPropertyUsingGet(): void
    {
        $valid = vr();

        $this->assertSame($valid, $valid->__get('unknown_property'));
    }

    public function testCanCallUnknownMethodUsingCall(): void
    {
        $valid = vr();

        $this->assertSame($valid, $valid->__call('unknown_method', []));
    }

    public function testCanRenderEqualsFieldUsingCall(): void
    {
        $this->assertEquals('equalsfield,other', vr()->equals_field('other'));
    }
}
