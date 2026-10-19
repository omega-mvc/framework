<?php

declare(strict_types=1);

namespace Tests\Validator\Validations\Methods;

use Omega\Validator\Validator;
use Tests\TestCase;

final class RawTest extends TestCase
{
    public function testCanAddValidationUsingRaw(): void
    {
        $validation = new Validator(['test' => 'test']);

        $validation->field('test')->raw('required');

        $this->assertTrue($validation->isValid());
    }

    public function testCanAddValidationUsingRawCombineWithOther(): void
    {
        $validation = new Validator(['test' => 'test']);

        $validation->field('test')->raw('required')->min_len(5);

        $this->assertFalse($validation->isValid());
    }
}
