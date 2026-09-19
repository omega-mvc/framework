<?php

use Omega\Validator\Rule\Valid;

it('can create rule using with method', function () {
    $rule = Valid::with()->required();

    expect($rule)->toEqual('required');
});

it('can create rule using with method from instance', function () {
    $rule = (new Valid())->with()->required();

    expect($rule)->toEqual('required');
});