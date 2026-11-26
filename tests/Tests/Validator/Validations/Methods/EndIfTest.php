<?php

declare(strict_types=1);

namespace Tests\Validator\Validations\Methods;

use Omega\Validator\Rule\Valid;
use ReflectionProperty;
use Tests\TestCase;

use function Omega\Validator\vr;

final class EndIfTest extends TestCase
{
    public function testCanRenderEndIfValidation(): void
    {
        $this->assertEquals('required', vr()->if(fn (): bool => true)->end_if()->required());
    }

    public function testCanRenderBothIfAndEndIfValidation(): void
    {
        $this->assertEquals('invert_required', vr()->not()->if(fn (): bool => false)->end_if()->required());
    }

    public function testCanDirectlyAppendTheEndIfRule(): void
    {
        $this->assertEquals('', (new Valid())->end_if()->getValidation());
    }

    public function testAppendsTheEndIfMarkerToTheValidationRulesArray(): void
    {
        $property = new ReflectionProperty(Valid::class, 'validation_rule');

        $this->assertSame(['end_if'], $property->getValue((new Valid())->end_if()));
    }

    public function testCanRenderIfTrueConditionAsLastRule(): void
    {
        $this->assertSame('', (new Valid())->if(fn (): bool => true)->getValidation());
    }

    public function testCanRenderIfFalseConditionAsLastRule(): void
    {
        $this->assertSame('', (new Valid())->if(fn (): bool => false)->getValidation());
    }

    public function testSkipsTheRulesWhileTheBlockedConditionIsActive(): void
    {
        $this->assertSame('', (new Valid())->if(fn (): bool => false)->required()->getValidation());
    }
}
