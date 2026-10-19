<?php

declare(strict_types=1);

namespace Tests\Validator\Validations\Methods;

use Omega\Validator\Validator;
use Tests\TestCase;

use function Omega\Validator\vr;

final class WhereTest extends TestCase
{
    public function testCanRenderValidationRuleUsingMethodWhereTrue(): void
    {
        $this->assertEquals('required', vr()->required()->where(fn (): bool => true));
    }

    public function testCanRenderValidationRuleUsingMethodWhereFalse(): void
    {
        $this->assertEquals('', vr()->required()->where(fn (): bool => false));
    }

    public function testCanResetValidationRuleUsingMethodWhereTrue(): void
    {
        $val = new Validator();

        // output: required
        $val->field('test')->required()->where(fn (): bool => true);

        $this->assertFalse($val->isValid());
    }

    public function testCanResetValidationRuleUsingMethodWhereFalse(): void
    {
        $val = new Validator();

        // output: ''
        $val->test->required()->where(fn (): bool => false);

        $this->assertTrue($val->isValid());
    }

    public function testCanResetValidationRuleUsingMethodWhereNoReturn(): void
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Condition closure not return boolean');

        $val = new Validator();

        // output: ''
        $val->test->required()->where(function () {
            // empty function
        });
    }

    public function testCanResetValidationRuleUsingMethodWhereNoBoolean(): void
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Condition closure not return boolean');

        $val = new Validator();

        // output: throw error
        $val->test->required()->where(fn (): string => 'test');
    }

    public function testCanValidateCombineWithSubmittedMethod(): void
    {
        $previous                  = $_SERVER['REQUEST_METHOD'] ?? null;
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $val                       = new Validator();

        try {
            // output: required
            $val->field('test')->required()->where(fn (): bool => $val->submitted());

            $this->assertFalse($val->isValid());
        } finally {
            if ($previous === null) {
                unset($_SERVER['REQUEST_METHOD']);
            } else {
                $_SERVER['REQUEST_METHOD'] = $previous;
            }
        }
    }
}
