<?php

declare(strict_types=1);

namespace Tests\Validator\Filters;

use Omega\Validator\Validator;
use Tests\TestCase;

final class CostumeFilterTest extends TestCase
{
    public function testCanAddCostumeFilter(): void
    {
        $validation = new Validator(['test' => 'test']);

        $validation->filter('test')->filter(fn ($value, $param = []) => 'ok ' . $value);

        $this->assertEquals(['test' => 'ok test'], $validation->filters->all());
    }

    public function testCanAddCostumeFilterCombineWithOther(): void
    {
        $validation = new Validator(['test' => 'test']);

        $validation->filter('test')
            ->filter(fn ($value, $param = []) => 'ok ' . $value)
            ->upper_case();

        $this->assertEquals(['test' => 'OK TEST'], $validation->filters->all());
    }
}
