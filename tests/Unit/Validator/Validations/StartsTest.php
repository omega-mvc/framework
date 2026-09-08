<?php

it('can render starts validation')
    ->expect(vr()->starts('Z'))
    ->toEqual('starts,Z')
;

it('can render invert starts validation')
    ->expect(vr()->not()->starts('Z'))
    ->toEqual('invert_starts,Z')
;

// validate with correct input field

it('can validate regex with correct input', function () {
    $val = new Omega\Validator\Validator(['test' => 'test']);

    $val->test->starts('tes');

    expect($val->isValid())->toBeTrue();
});

it('can validate required (not) with correct input', function () {
    $val = new Omega\Validator\Validator(['test' => 'test']);

    $val->test->not->starts('tes');

    expect($val->isValid())->toBeFalse();
});

// validate with incorrect input field

it('can validate required with incorrect input', function () {
    $val = new Omega\Validator\Validator(['test' => 'ttest']);

    $val->test->starts('tes');

    expect($val->isValid())->toBeFalse();
});

it('can validate regex (not) with incorrect input', function () {
    $val = new Omega\Validator\Validator(['test' => 'ttest']);

    $val->test->not->starts('tes');

    expect($val->isValid())->toBeTrue();
});
