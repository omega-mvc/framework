<?php

it('can render valid_json_string validation')
    ->expect(vr()->valid_json_string())
    ->toEqual('valid_json_string')
;

it('can render invert valid_json_string validation')
    ->expect(vr()->not()->valid_json_string())
    ->toEqual('invert_valid_json_string')
;

$correct = [
    'test1' => '{}',
    'test2' => '{"testing": true}',
];
$incorrect = [
    'tets1' => '{}}',
    'tets2' => '{test:true}',
    'tets3' => '{"test":text}',
];

// validate with correct input field

it('can validate valid_json_string with correct input', function () use ($correct) {
    $val = new Omega\Validator\Validator($correct);

    $field_name = array_keys($correct);
    $val->field(...$field_name)->valid_json_string();

    expect($val->isValid())->toBeTrue();
});

it('can validate valid_json_string (not) with correct input', function () use ($correct) {
    $val = new Omega\Validator\Validator($correct);

    $field_name = array_keys($correct);
    $val->field(...$field_name)->not->valid_json_string();

    expect($val->isValid())->toBeFalse();
});

// validate with incorrect input field

it('can validate valid_json_string with incorrect input', function () use ($incorrect) {
    $val = new Omega\Validator\Validator($incorrect);

    $field_name = array_keys($incorrect);
    $val->field(...$field_name)->valid_json_string();

    expect($val->isValid())->toBeFalse();
});

it('can validate valid_json_string (not) with incorrect input', function () use ($incorrect) {
    $val = new Omega\Validator\Validator($incorrect);

    $field_name = array_keys($incorrect);
    $val->field(...$field_name)->not->valid_json_string();

    expect($val->isValid())->toBeTrue();
});
