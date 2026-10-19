<?php

declare(strict_types=1);

namespace Tests\Validator;

use Omega\Validator\Validator;
use PHPUnit\Framework\Attributes\CoversClass;
use Tests\TestCase;

#[CoversClass(Validator::class)]
final class OtherPropertyValidationTest extends TestCase
{
    public function testPropertyNotSameResultWithMethodNot(): void
    {
        $val = new Validator();

        $val->field('test')->not->required();
        $val->test2->not->required();

        $this->assertTrue($val->isValid());
    }
}
