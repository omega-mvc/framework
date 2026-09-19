<?php

use function Omega\Validator\vr;

it('can render doesnt_contain_list validation')
    ->expect(vr()->doesnt_contain_list('one', 'two'))
    ->toEqual('doesnt_contain_list,one;two')
;

it('can render invert doesnt_contain_list validation')
    ->expect(vr()->not()->doesnt_contain_list('one', 'two'))
    ->toEqual('invert_doesnt_contain_list,one;two')
;

$correct = [
    'test1' => 'three',
    'test2' => 'four',
];
$incorrect = [
    'test' => 'one',
];

it('can validate doesnt_contain_list with correct input', function () use ($correct) {
    $val = new Omega\Validator\Validator($correct);

    $val->field('test1', 'test2')->doesnt_contain_list('one', 'two');

    expect($val->isValid())->toBeTrue();
});

it('can validate doesnt_contain_list (not) with correct input', function () use ($correct) {
    $val = new Omega\Validator\Validator($correct);

    $val->field('test1', 'test2')->not->doesnt_contain_list('one', 'two');

    expect($val->isValid())->toBeFalse();
});

it('can validate doesnt_contain_list with incorrect input', function () use ($incorrect) {
    $val = new Omega\Validator\Validator($incorrect);

    $val->field('test')->doesnt_contain_list('one', 'two');

    expect($val->isValid())->toBeFalse();
});

it('can validate doesnt_contain_list (not) with incorrect input', function () use ($incorrect) {
    $val = new Omega\Validator\Validator($incorrect);

    $val->field('test')->not->doesnt_contain_list('one', 'two');

    expect($val->isValid())->toBeTrue();
});