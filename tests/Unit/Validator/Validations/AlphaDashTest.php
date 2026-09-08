<?php

it('can render alpha_dash validation')
    ->expect(vr()->alpha_dash())
    ->toEqual('alpha_dash')
;

it('can render invert alpha_dash validation')
    ->expect(vr()->not()->alpha_dash())
    ->toEqual('invert_alpha_dash')
;

$correct   = ['test' => 'my_username-with_dash'];
$incorrect = ['test' => 'hello123'];

// validate with correct input field
it('can validate alpha with correct input', function () use ($correct) {
    $val = new Omega\Validator\Validator($correct);
    $val->test->alpha_dash();

    expect($val->isValid())->toBeTrue();
});

it('can validate alpha (not) with correct input', function () use ($correct) {
    $val = new Omega\Validator\Validator($correct);
    $val->test->not()->alpha_dash();

    expect($val->isValid())->toBeFalse();
});

// validate with incorrect input field
it('can validate alpha with incorrect input', function () use ($incorrect) {
    $val = new Omega\Validator\Validator($incorrect);
    $val->test->alpha_dash();

    expect($val->isValid())->toBeFalse();
});

it('can validate alpha (not) with incorrect input', function () use ($incorrect) {
    $val = new Omega\Validator\Validator($incorrect);
    $val->test->not()->alpha_dash();

    expect($val->isValid())->toBeTrue();
});
