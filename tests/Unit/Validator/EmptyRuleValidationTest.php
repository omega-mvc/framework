<?php

declare(strict_types=1);

namespace Tests\Validator;

use Omega\Validator\Validator;
use Tests\TestCase;

final class EmptyRuleValidationTest extends TestCase
{
    public function testEmptyRuleNotMakeRuntimeError(): void
    {
        $valid = new Validator(['test' => 'test']);
        // set empty rull
        $valid->field('test');

        $this->assertTrue($valid->isValid());
    }

    public function testUsingNotWithEmptyRuleNotMakeRuntimeError(): void
    {
        $valid = new Validator(['test' => 'test']);
        // set empty rull
        $valid->field('test')->not();

        $this->assertTrue($valid->isValid());
    }
}
