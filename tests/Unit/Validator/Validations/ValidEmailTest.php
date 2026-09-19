<?php

use function Omega\Validator\vr;

it('can render valid_email validation')
    ->expect(vr()->valid_email())
    ->toEqual('valid_email')
;

it('can render invert valid_email validation')
    ->expect(vr()->not->valid_email())
    ->toEqual('invert_valid_email')
;

$correct = [
    'test1' => 'test@gmail.com',
    'test2' => 'test@yahoo.com',
    'test3' => 'test@hotmail.com',
];
$incorrect = [
    'test1' => 'test',
    'test2' => 'test@gmail',
];

// validate with correct input field

it('can validate valid_email with correct input', function () use ($correct) {
    $val = new Omega\Validator\Validator($correct);

    $field_name = array_keys($correct);
    $val->field(...$field_name)->valid_email();

    expect($val->isValid())->toBeTrue();
});

it('can validate valid_email (not) with correct input', function () use ($correct) {
    $val = new Omega\Validator\Validator($correct);

    $field_name = array_keys($correct);
    $val->field(...$field_name)->not->valid_email();

    expect($val->isValid())->toBeFalse();
});

// validate with incorrect input field

it('can validate valid_email with incorrect input', function () use ($incorrect) {
    $val = new Omega\Validator\Validator($incorrect);

    $field_name = array_keys($incorrect);
    $val->field(...$field_name)->valid_email();

    expect($val->isValid())->toBeFalse();
});

it('can validate valid_email (not) with incorrect input', function () use ($incorrect) {
    $val = new Omega\Validator\Validator($incorrect);

    $field_name = array_keys($incorrect);
    $val->field(...$field_name)->not->valid_email();

    expect($val->isValid())->toBeTrue();
});