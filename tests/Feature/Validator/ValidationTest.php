<?php

declare(strict_types=1);

namespace Tests\Feature\Validator;

use LogicException;
use Omega\Validator\Rule\ValidPool;
use Omega\Validator\Validator;
use PHPUnit\Framework\Attributes\CoversClass;
use Tests\TestCase;

#[CoversClass(Validator::class)]
final class ValidationTest extends TestCase
{
    public function testValidateUsingFieldsAndValidation(): void
    {
        $input = [
            'id'       => 1,
            'user'     => 'teguh',
            'name'     => 'teguh agus',
            'favorite' => ['manggo', 'durian', 'start fruite'],
        ];
        $val = new Validator($input);

        $val->id->required()->integer();
        $val->field('user')->required()->min_len(5);
        $val('name')->required()->valid_name();

        $this->assertTrue($val->isValid());
    }

    public function testRunValidationConditionWithValidCondition(): void
    {
        $input = [
            'id'       => 1,
            'user'     => 'teguh',
            'name'     => 'teguh agus',
            'favorite' => ['manggo', 'durian', 'start fruite'],
        ];
        $val = new Validator($input);

        $val->id->required()->integer();
        $val->field('user')->required()->min_len(5);
        $val('name')->required()->valid_name();

        $val->ifValid(function () use ($val): void {
            $this->assertTrue($val->isValid());
        })->else(function (array $err): void {
            // its mean have no error
            $this->assertCount(0, $err);
        });
    }

    public function testRunValidationConditionWithValidFailedCondition(): void
    {
        $input = [
            'id'       => null,
            'user'     => 'tgh',
            'name'     => 'teguh agus',
            'favorite' => ['manggo', 'durian', 'start fruite'],
        ];
        $val = new Validator($input);

        $val->id->required()->integer();
        $val->field('user')->required()->min_len(5);
        $val('name')->required()->valid_name();

        $val->ifValid(function (): void {
            // skip, invalid validation
            throw new LogicException('This branch should never run for invalid input.');
        })->else(function (array $err) use ($val): void {
            $this->assertFalse($val->isValid());
        });
    }

    public function testCanValidateNestingArray(): void
    {
        $test = new Validator([
            'name' => 'angger',
            'nest' => [
                'number' => 12,
                'string' => 'a string',
            ],
            'users' => [
                ['name' => 'ulfa', 'age' => 21],
                ['name' => 'haikal', 'age' => 1],
            ],
        ]);

        $valid = $test->isValid(function (ValidPool $valid): ValidPool {
            $valid('name')->required()->max_len(7);
            $valid('nest.number')->required();
            $valid('users.*.name')->required();

            return $valid;
        });

        $this->assertTrue($valid);
    }

    public function testCanValidateInvertNestingArray(): void
    {
        $test = new Validator([
            // 'name' => 'angger',
            'hoby' => 'plaing,game',
            'nest' => [
                'number' => 12,
                'string' => 'a string',
            ],
            'users' => [
                ['name' => 'ulfa', 'age' => 21],
                ['name' => 'haikal', 'age' => 1],
            ],
        ]);

        $valid = $test->isValid(function (ValidPool $valid): ValidPool {
            $valid('name')->not()->required();
            $valid('hoby')->not()->contains();
            $valid('nest.number')->required();
            $valid('users.*.name')->required();

            return $valid;
        });

        $this->assertTrue($valid);
    }
}
