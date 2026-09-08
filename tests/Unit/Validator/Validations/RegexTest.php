<?php

it('can render regex validation')
    ->expect(vr()->regex('/test-[0-9]{3}/'))
    ->toEqual('regex,/test-[0-9]{3}/')
;

it('can render invert regex validation')
    ->expect(vr()->not()->regex('/test-[0-9]{3}/'))
    ->toEqual('invert_regex,/test-[0-9]{3}/')
;

$correct   = ['test' => 'validation using gump'];
$incorrect = ['test' => 'testing using pest'];
// validate with correct input field

it('can validate regex with correct input', function () use ($correct) {
    $val = new Omega\Validator\Validator($correct);

    $val->test->regex('/gump/i');

    expect($val->isValid())->toBeTrue();
});

it('can validate regex (not) with correct input', function () use ($correct) {
    $val = new Omega\Validator\Validator($correct);

    $val->test->not->regex('/gump/i');

    expect($val->isValid())->toBeFalse();
});

// validate with incorrect input field

it('can validate regex with incorrect input', function () use ($incorrect) {
    $val = new Omega\Validator\Validator($incorrect);

    $val->test->regex('/gump/i');

    expect($val->isValid())->toBeFalse();
});

it('can validate regex (not) with incorrect input', function () use ($incorrect) {
    $val = new Omega\Validator\Validator($incorrect);

    $val->test->not()->regex('/gump/i');

    expect($val->isValid())->toBeTrue();
});
