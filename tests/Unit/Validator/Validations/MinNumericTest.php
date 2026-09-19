<?php

use function Omega\Validator\vr;

it('can render min_numeric validation')
    ->expect(vr()->min_numeric(5))
    ->toEqual('min_numeric,5')
;

it('can render invert min_numeric validation')
    ->expect(vr()->not->min_numeric(5))
    ->toEqual('invert_min_numeric,5')
;

$correct = [
    'test1' => 5,
    'test2' => 10,
    'test3' => '',
];
$incorrect = [
    'test' => 3,
];

// validate with correct input field

it('can validate min_numeric with correct input', function () use ($correct) {
    $val = new Omega\Validator\Validator($correct);

    $val->field('test1', 'test2', 'test3')->min_numeric(5);

    expect($val->isValid())->toBeTrue();
});

it('can validate min_numeric (not) with correct input', function () use ($correct) {
    $val = new Omega\Validator\Validator($correct);

    $val->field('test1', 'test2', 'test3')->not->min_numeric(5);

    expect($val->isValid())->toBeFalse();
});

// validate with incorrect input field

it('can validate min_numeric with incorrect input', function () use ($incorrect) {
    $val = new Omega\Validator\Validator($incorrect);

    // less
    $val->field('test')->min_numeric(5);

    expect($val->isValid())->toBeFalse();
});

it('can validate min_numeric (not) with incorrect input', function () use ($incorrect) {
    $val = new Omega\Validator\Validator($incorrect);

    // less
    $val->field('test')->not->min_numeric(5);

    expect($val->isValid())->toBeTrue();
});