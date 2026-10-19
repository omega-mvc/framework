<?php

declare(strict_types=1);

namespace Tests\Validator;

use Omega\Validator\Rule\Filter;
use Omega\Validator\Rule\Valid;
use Omega\Validator\Validator;
use PHPUnit\Framework\Attributes\CoversFunction;
use Tests\TestCase;

use function Omega\Validator\fr;
use function Omega\Validator\validate;
use function Omega\Validator\vr;
final class HelperTest extends TestCase
{
    public function testVrHelperCreatesAValidRule(): void
    {
        $instance = vr();

        $this->assertSame(Valid::class, $instance::class);
    }

    public function testFrHelperCreatesAFilterRule(): void
    {
        $instance = fr();

        $this->assertSame(Filter::class, $instance::class);
    }

    public function testValidateHelperCreatesAValidatorWithTheGivenFields(): void
    {
        $validator = validate(['name' => 'omega']);

        $this->assertSame(Validator::class, $validator::class);
        $this->assertSame(['name' => 'omega'], $validator->getFields());
    }
}
