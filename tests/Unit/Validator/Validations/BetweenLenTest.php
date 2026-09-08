<?php

it('can render between_len validation')
    ->expect(vr()->between_len(3, 11))
    ->toEqual('between_len,3;11')
;

it('can render invert between_len validation')
    ->expect(vr()->not()->between_len(3, 11))
    ->toEqual('invert_between_len,3;11')
;

$correct   = ['test' => '123'];
$incorrect = ['test1' => '1', 'test2' => '123456'];

// validate with correct input field
it('can validate between_len with correct input', function () use ($correct) {
    $val = new Omega\Validator\Validator($correct);
    $val->test->between_len(2, 5);

    expect($val->isValid())->toBeTrue();
});

it('can validate between_len (not) with correct input', function () use ($correct) {
    $val = new Omega\Validator\Validator($correct);
    $val->test->not()->between_len(2, 5);

    expect($val->isValid())->toBeFalse();
});

// validate with incorrect input field
it('can validate between_len with incorrect input', function () use ($incorrect) {
    $val = new Omega\Validator\Validator($incorrect);
    // less that & more that
    $val->field('test1', 'test2')->between_len(2, 5);

    expect($val->isValid())->toBeFalse();
});

it('can validate between_len (not) with incorrect input', function () use ($incorrect) {
    $val = new Omega\Validator\Validator($incorrect);
    // less that & more that
    $val->field('test1', 'test2')->not->between_len(2, 5);

    expect($val->isValid())->toBeTrue();
});
