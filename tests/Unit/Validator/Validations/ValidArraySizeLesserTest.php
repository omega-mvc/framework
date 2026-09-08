<?php

it('can render valid_array_size_lesser validation')
    ->expect(vr()->valid_array_size_lesser(1))
    ->toEqual('valid_array_size_lesser,1')
;

it('can render invert valid_array_size_lesser validation')
    ->expect(vr()->not()->valid_array_size_lesser(1))
    ->toEqual('invert_valid_array_size_lesser,1')
;

$correct   = [
    'test1' => [1, 2, 3],
    'test2' => '',
];
$incorrect = ['test' => [1, 2]];

// validate with correct input field

it('can validate valid_array_size_lesser with correct input', function () use ($correct) {
    $val = new Omega\Validator\Validator($correct);

    $val->test1->valid_array_size_lesser(3);
    $val->test1->valid_array_size_lesser(4);
    $val->test2->valid_array_size_lesser(3);

    expect($val->isValid())->toBeTrue();
});

it('can validate valid_array_size_lesser (not) with correct input', function () use ($correct) {
    $val = new Omega\Validator\Validator($correct);

    $val->test1->not->valid_array_size_lesser(3);
    $val->test1->not->valid_array_size_lesser(4);
    $val->test2->not->valid_array_size_lesser(3);

    expect($val->isValid())->toBeFalse();
});

// validate with incorrect input field

it('can validate valid_array_size_lesser with incorrect input', function () use ($incorrect) {
    $val = new Omega\Validator\Validator($incorrect);

    $val->test->valid_array_size_lesser(1);

    expect($val->isValid())->toBeFalse();
});

it('can validate regex (not) with incorrect input', function () use ($incorrect) {
    $val = new Omega\Validator\Validator($incorrect);

    $val->test->not->valid_array_size_lesser(1);

    expect($val->isValid())->toBeTrue();
});
