<?php

use Omega\Validator\Validator;

// empty rule valdation rule
it('empty rule not make runtime error', function () {
    $valid = new Validator(['test' => 'test']);
    // set empty rull
    $valid->field('test');
    expect($valid->isValid())->toBeTrue();
});

it('using \'not\' with empty rule not make runtime error', function () {
    $valid = new Validator(['test' => 'test']);
    // set empty rull
    $valid->field('test')->not();
    expect($valid->isValid())->toBeTrue();
});
