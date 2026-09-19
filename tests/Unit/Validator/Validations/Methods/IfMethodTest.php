<?php

use Omega\Validator\Validator;

it('can execute method using if (true)', function () {
    $val = new Validator();

    // output: required
    $val->field('test')->if(fn () => true)->required();

    expect($val->isValid())->toBeFalse();
});

it('can execute method using if (false)', function () {
    $val = new Validator();

    // output: required
    $val->field('test')->if(fn () => false)->required();

    expect($val->isValid())->toBeTrue();
});

it('can execute method using if (true, true)', function () {
    $val = new Validator(['test' => '200']);

    // output: numeric|min_len,2
    $val->field('test')->if(fn () => true)->numeric()->if(fn () => true)->min_len(2);

    expect($val->isValid())->toBeTrue();
});

it('can execute method using if (true, false)', function () {
    $val = new Validator(['test' => '1']);

    // output: numeric
    $val->field('test')->if(fn () => true)->numeric()->if(fn () => false)->min_len(2);

    expect($val->isValid())->toBeTrue();
});

it('can execute method using if (false, true)', function () {
    $val = new Validator(['test' => 'test']);

    // output: min_len
    $val->field('test')->if(fn () => false)->numeric()->if(fn () => true)->min_len(2);

    expect($val->isValid())->toBeTrue();
});

it('can execute method using if (false, false)', function () {
    $val = new Validator(['test' => 'text']);

    // output: ''
    $val->field('test')->if(fn () => false)->numeric()->if(fn () => false)->min_len(2);

    expect($val->isValid())->toBeTrue();
});

it('can execute method using if-contiune (true, true)', function () {
    $val = new Validator(['test' => 'test']);

    // output: 'required'
    $val->field('test')->if(fn () => true)->if(fn () => true)->required();

    expect($val->isValid())->toBeTrue();
});

it('can execute method using if-contiune (true, false) x', function () {
    $val = new Validator(['test' => 'text']);

    // output: ''
    $val->field('test')->if(fn () => true)->if(fn () => false)->alpha();

    expect($val->isValid())->toBeTrue();
});

it('can execute method using if-contiune (false, false)', function () {
    $val = new Validator(['test' => 'text']);

    // output: ''
    $val->field('test')->if(fn () => false)->if(fn () => false)->min_len(2);

    expect($val->isValid())->toBeTrue();
});

it('can execute method using if-contiune (false, true)', function () {
    $val = new Validator(['test' => 'text']);

    // output: ''
    $val->field('test')->if(fn () => false)->if(fn () => true)->min_len(2);

    expect($val->isValid())->toBeTrue();
});

it('can throw exception using if (no return)', function () {
    $val = new Validator(['test' => 'test']);

    $val->field('test')->if(function () {
        // no return
    });
})->throws('Condition closure not return boolean');

it('can throw exception using if (no boolean)', function () {
    $val = new Validator(['test' => 'test']);

    $val->field('test')->if(fn () => 'test');
})->throws('Condition closure not return boolean');

it('can validate combine with submitted method', function () {
    $previous            = $_SERVER['REQUEST_METHOD'] ?? null;
    $_SERVER['REQUEST_METHOD'] = 'POST';
    $val                 = new Validator();

    try {
        // output: required
        $val->field('test')->if(fn () => $val->submitted())->required();

        expect($val->isValid())->toBeFalse();
    } finally {
        if ($previous === null) {
            unset($_SERVER['REQUEST_METHOD']);
        } else {
            $_SERVER['REQUEST_METHOD'] = $previous;
        }
    }
});
