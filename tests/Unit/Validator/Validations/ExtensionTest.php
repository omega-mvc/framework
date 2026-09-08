<?php

it('can render extension validation')
    ->expect(vr()->extension('png', 'jpg', 'gif'))
    ->toEqual('extension,png;jpg;gif')
;

it('can render invert extension validation')
    ->expect(vr()->not->extension('png', 'jpg', 'gif'))
    ->toEqual('invert_extension,png;jpg;gif')
;

$correct = [
    'files' => [
        'test' => [
            'name'     => 'screenshot.png',
            'type'     => 'image/png',
            'tmp_name' => '/tmp/phphjatI9',
            'error'    => 0,
            'size'     => 22068,
        ],
    ],
];
$incorrect = [
    'files' => [
        'test' => [
            'name'     => 'screenshot.png',
            'type'     => 'image/png',
            'tmp_name' => '/tmp/phphjatI9',
            'error'    => 4,
            'size'     => 22068,
        ],
    ],
];

// validate with correct input field

it('can validate extension with correct input', function () use ($correct) {
    $val = new Omega\Validator\Validator($correct);

    $val->field('files.test')->extension('png');

    expect($val->isValid())->toBeTrue();
});

it('can validate extension (not) with correct input', function () use ($correct) {
    $val = new Omega\Validator\Validator($correct);

    $val->field('files.test')->not->extension('png');

    expect($val->isValid())->toBeFalse();
});

// validate with incorrect input field

it('can validate extension with incorrect input', function () use ($incorrect) {
    $val = new Omega\Validator\Validator($incorrect);

    $val->field('files.test')->extension('php');

    expect($val->isValid())->toBeFalse();
});

it('can validate extension (not) with incorrect input', function () use ($incorrect) {
    $val = new Omega\Validator\Validator($incorrect);

    $val->field('files.test')->not->extension('php');

    expect($val->isValid())->toBeTrue();
});
