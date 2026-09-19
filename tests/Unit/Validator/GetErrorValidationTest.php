<?php

use Omega\Validator\Rule;
use Omega\Validator\Validator;

// get error message
it('can get error message when valadation is fallen using method get_error', function () {
    $valid = new Validator(['test' => 'test']);

    $valid->field('test')->min_len(5);

    expect($valid->getError())->toHaveCount(1);
});

it('can get error message when valadation is fallen using method ifValid', function () {
    $valid = new Validator(['test' => 'test']);

    $valid->field('test')->min_len(5);

    $valid->ifValid(function () {
    })->else(function ($err) {
        expect($err)->toHaveCount(1);
    });
});

it('can get error message when valadation is fallen using method validOrError', function () {
    $valid = new Validator(['test' => 'test']);

    $valid->field('test')->min_len(5);

    expect($valid->validOrError())->toHaveCount(1);
});

it('can get error from method errors', function () {
    Rule::set_error_message('required', '{field} can\'t be null');

    $v = new Validator(['test' => null, 'test2' => 'abc']);

    $v('test')->required();
    $v->filter('test2')->upper_case();

    expect($v->errors()->all())->toMatchArray([
        'test' => 'Test can\'t be null',
    ]);
    expect($v->errors->has('test'))->toBeTrue();
});

it('can get error from property errors', function () {
    Rule::set_error_message('required', '{field} can\'t be null');

    $v = new Validator(['test' => null, 'test2' => 'abc']);

    $v('test')->required();
    $v->filter('test2')->upper_case();

    expect($v->errors)
        ->has('test')->toBeTrue()
        ->all()->toMatchArray([
            'test' => 'Test can\'t be null',
        ]);
});
