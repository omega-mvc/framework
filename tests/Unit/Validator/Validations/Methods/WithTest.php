<?php

declare(strict_types=1);

namespace Tests\Validator\Validations\Methods;

use Omega\Validator\Rule\Valid;
use Tests\TestCase;

final class WithTest extends TestCase
{
    public function testCanCreateRuleUsingWithMethod(): void
    {
        $rule = Valid::with()->required();

        $this->assertEquals('required', $rule);
    }

    public function testCanCreateRuleUsingWithMethodFromInstance(): void
    {
        $rule = (new Valid())->with()->required();

        $this->assertEquals('required', $rule);
    }
}
