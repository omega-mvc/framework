<?php

declare(strict_types=1);

namespace Tests\Validator;

use Omega\Validator\Validator;
use Tests\TestCase;

final class CreateUsingStaticTest extends TestCase
{
    public function testCanCreateStatic(): void
    {
        $this->assertEquals([], Validator::make()->getFields());
    }
}
