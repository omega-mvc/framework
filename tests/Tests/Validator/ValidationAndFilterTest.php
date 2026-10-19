<?php

declare(strict_types=1);

namespace Tests\Validator;

use Omega\Validator\Validator;
use PHPUnit\Framework\Attributes\CoversClass;
use Tests\TestCase;

#[CoversClass(Validator::class)]
final class ValidationAndFilterTest extends TestCase
{
    public function testCanValidateAndFilterInOneLine(): void
    {
        $input = [
            'id'       => 1,
            'user'     => ' teguh ',
            'name'     => 'teguh agus',
            'favorite' => ['manggo', 'durian', 'start fruite'],
        ];
        $val = new Validator($input);

        // validate rule
        $val->id->required()->integer();
        $val->field('user')->required()->min_len(5);
        $val('name')->required()->valid_name();
        // filter rule
        $val->filter('user')->upper_case()->trim();

        $this->assertEquals(
            [
                'id'       => 1,
                'user'     => 'TEGUH',
                'name'     => 'teguh agus',
                'favorite' => ['manggo', 'durian', 'start fruite'],
            ],
            $val->failedOrFilter()
        );
    }
}
