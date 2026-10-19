<?php

declare(strict_types=1);

namespace Tests\Validator\Validations\Methods;

use Omega\Validator\Rule\Valid;
use Tests\TestCase;

final class CombineTest extends TestCase
{
    public function testCanCombineValidClassWithOtherValidClass(): void
    {
        $valid = new Valid();
        $valid->required();

        $valid2 = new Valid();
        $valid2->alpha();
        $valid2->combine($valid);

        $this->assertEquals('alpha|required', $valid2->getValidation());
    }
}
