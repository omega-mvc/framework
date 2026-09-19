<?php

use function Omega\Validator\vr;

it('can render contains_list validation')
    ->expect(vr()->contains_list('one', 'two'))
    ->toEqual('contains_list,one;two')
;

it('can render invert contains_list validation')
    ->expect(vr()->not()->contains_list('one', 'two'))
    ->toEqual('invert_contains_list,one;two')
;

$correct = [
    'test1' => 'one',
    'test2' => 'with space',
    'test3' => '',
];
$incorrect = [
    'test' => 'three',
];

it('can validate contains_list with correct input', function () use ($correct) {
    $val = new Omega\Validator\Validator($correct);

    $val->field('test1', 'test2', 'test3')->contains_list('one', 'two', 'with space');

    expect($val->isValid())->toBeTrue();
});

it('can validate contains_list with incorrect input', function () use ($incorrect) {
    $val = new Omega\Validator\Validator($incorrect);

    $val->field('test')->contains_list('one', 'two');

    expect($val->isValid())->toBeFalse();
});