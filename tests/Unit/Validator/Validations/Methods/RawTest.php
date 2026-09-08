<?php

use Omega\Validator\Validator;

it('can add validation using raw', function () {
    $validation = new Validator(['test' => 'test']);

    $validation->field('test')->raw('required');

    expect($validation->isValid())->toBeTrue();
});

it('can add validation using raw combine with other', function () {
    $validation = new Validator(['test' => 'test']);

    $validation->field('test')->raw('required')->min_len(5);

    expect($validation->isValid())->toBeFalse();
});
