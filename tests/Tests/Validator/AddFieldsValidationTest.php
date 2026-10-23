<?php

declare(strict_types=1);

namespace Tests\Validator;

use ArrayIterator;
use IteratorAggregate;
use Omega\Validator\Validator;
use Tests\TestCase;
use Traversable;

final class AddFieldsValidationTest extends TestCase
{
    public function testCanAddFieldUsingConstructor(): void
    {
        $fields = [
            'field_1' => 'field_1',
            'field_2' => 'field_3',
            'field_3' => 'field_3',
        ];

        $valid = new Validator($fields);

        $this->assertEquals($fields, $valid->getFields());
    }

    public function testCanAddFieldUsingStaticMethodMake(): void
    {
        $fields = [
            'field_1' => 'field_1',
            'field_2' => 'field_3',
            'field_3' => 'field_3',
        ];

        $this->assertEquals($fields, Validator::make($fields)->getFields());
    }

    public function testCanAddFieldUsingMethodFields(): void
    {
        $fields = [
            'field_1' => 'field_1',
            'field_2' => 'field_3',
            'field_3' => 'field_3',
        ];

        $valid = new Validator();
        $valid->fields($fields);

        $this->assertEquals($fields, $valid->getFields());
    }

    public function testCanAddFieldUsingMethodFieldsEmpty(): void
    {
        $valid = new Validator();
        $valid->fields([]);

        $this->assertEquals([], $valid->getFields());
    }

    public function testCanAddFieldUsingIteratorArray(): void
    {
        $fields = new class () implements IteratorAggregate {
            /** @var array<string, string> */
            private array $fields = [
                'field_1' => 'test',
                'field_2' => 'test',
            ];

            public function getIterator(): Traversable
            {
                return new ArrayIterator($this->fields);
            }
        };

        $valid = new Validator();
        $valid->fields($fields);

        $this->assertEquals([
            'field_1' => 'test',
            'field_2' => 'test',
        ], $valid->getFields());
    }
}
