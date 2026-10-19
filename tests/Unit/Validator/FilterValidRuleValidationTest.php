<?php

declare(strict_types=1);

namespace Tests\Validator;

use Omega\Validator\Rule\ValidPool;
use Omega\Validator\Validator;
use Tests\TestCase;

final class FilterValidRuleValidationTest extends TestCase
{
    public function testCanValidateWithNormalBehavior(): void
    {
        $v = Validator::make(
            [
                'test1' => 'test',
                'test2' => 'test',
                'test3' => '',
            ],
            fn (ValidPool $v): array => [
                $v('test1')->required(),
                $v('test2')->required(),
                $v('test3')->required(),
            ]
        );

        $this->assertFalse($v->isValid());
    }

    public function testCanFilterValidRuleWithAllowedField(): void
    {
        $v = Validator::make(
            [
                'test1' => 'test',
                'test2' => 'test',
                'test3' => '',
            ],
            fn (ValidPool $v): array => [
                $v('test1')->required(),
                $v('test2')->required(),
                $v('test3')->required(),
            ]
        );

        $v->only(['test1', 'test2']);

        $this->assertTrue($v->isValid());
    }

    public function testCanFilterValidRuleWithAllowedFieldFalse(): void
    {
        $v = Validator::make(
            [
                'test1' => 'test',
                'test2' => 'test',
                'test3' => '',
            ],
            fn (ValidPool $v): array => [
                $v('test1')->required(),
                $v('test2')->required(),
                $v('test3')->required(),
            ]
        );
        $v->only(['test3']);

        $this->assertFalse($v->isValid());
    }

    public function testCanFilterValidRuleWithAllowedFieldButNotExis(): void
    {
        $v = Validator::make(
            [
                'test1' => 'test',
                'test2' => 'test',
                'test3' => '',
            ],
            fn (ValidPool $v): array => [
                $v('test1')->required(),
                $v('test2')->required(),
                $v('test3')->required(),
            ]
        );

        $v->only(['test4']);

        $this->assertTrue($v->isValid());
    }

    public function testCanFilterValidRuleWithExceptedField(): void
    {
        $v = Validator::make(
            [
                'test1' => 'test',
                'test2' => 'test',
                'test3' => '',
            ],
            fn (ValidPool $v): array => [
                $v('test1')->required(),
                $v('test2')->required(),
                $v('test3')->required(),
            ]
        );

        $v->except(['test1', 'test2']);

        $this->assertFalse($v->isValid());
    }

    public function testCanFilterValidRuleWithExceptedFieldButNotExis(): void
    {
        $v = Validator::make(
            [
                'test1' => 'test',
                'test2' => 'test',
                'test3' => '',
            ],
            fn (ValidPool $v): array => [
                $v('test1')->required(),
                $v('test2')->required(),
                $v('test3')->required(),
            ]
        );
        $v->except(['test4']);

        $this->assertFalse($v->isValid());
    }
}
