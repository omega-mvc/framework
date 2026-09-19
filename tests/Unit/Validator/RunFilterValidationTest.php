<?php

use Omega\Validator\Rule\FilterPool;
use Omega\Validator\Validator;

// run filter
it('can run filter using method filterOut', function () {
    $valid = new Validator([
        'test1' => 'test',
        'test2' => 'test',
    ]);

    $valid->filter('test1')->upper_case();

    expect($valid->filterOut())
        ->toEqual([
            'test1' => 'TEST',
            'test2' => 'test',
        ])
    ;
});

it('can run filter using method filterOut without filter rules', function () {
    $field = [
        'test1' => 'test',
        'test2' => 'test',
        'files' => [
            'test' => [
                'name'  => 'test',
                'error' => 4,
            ],
        ],
    ];

    $valid = new Validator($field);

    expect($valid->filterOut())->toEqual($field)
    ;
});

it('can run filter using method filterOut with closure (param)', function () {
    $valid = new Validator([
        'test1' => 'test',
        'test2' => ' test ',
        'test3' => 'TEST',
        'test4' => ' test ',
        'test5' => ' test ',
        'test6' => ' test ',
        'test7' => ' test ',
    ]);

    expect(
        $valid->filterOut(function (FilterPool $pool) {
            $pool->rule('test1')->upper_case();
            $pool->test2->trim();
            $pool('test3')->lower_case();
            $pool->rule('test4', 'test5')->trim();
            $pool('test6', 'test7')->trim();
        })
    )->toEqual([
        'test1' => 'TEST',
        'test2' => 'test',
        'test3' => 'test',
        'test4' => 'test',
        'test5' => 'test',
        'test6' => 'test',
        'test7' => 'test',
    ]);
});

it('can run filter using method filterOut with closure (return)', function () {
    $valid = new Validator([
        'test1' => 'test',
        'test2' => ' test ',
        'test3' => 'TEST',
        'test4' => ' test ',
        'test5' => ' test ',
        'test6' => ' test ',
        'test7' => ' test ',
    ]);

    expect(
        $valid->filterOut(function () {
            $pool = new FilterPool();
            $pool->rule('test1')->upper_case();
            $pool->test2->trim();
            $pool('test3')->lower_case();
            $pool->rule('test4', 'test5')->trim();
            $pool('test6', 'test7')->trim();

            return $pool;
        })
    )->toEqual([
        'test1' => 'TEST',
        'test2' => 'test',
        'test3' => 'test',
        'test4' => 'test',
        'test5' => 'test',
        'test6' => 'test',
        'test7' => 'test',
    ]);
});

it('can run filter using method filterOut with closure (param) but no rules', function () {
    $valid = new Validator(['field' => 'trim']);

    expect($valid->filterOut(static function (): FilterPool {
        return new FilterPool();
    }))->toEqual(['field' => 'trim']);
});

it('can run filter using method failedOrFilter', function () {
    $valid = new Validator(['test' => 'test']);

    $valid->field('test')->required();
    $valid->filter('test')->upper_case();

    expect($valid->failedOrFilter())
        ->toEqual(['test' => 'TEST'])
    ;
});

it('can run filter using method failedOrFilter but not valid', function () {
    $valid = new Validator(['test' => 'test']);

    $valid->field('test')->min_len(5);
    $valid->filter('test')->upper_case();

    expect($valid->failedOrFilter())->toBeTrue();
});

it('can get filterOut using filters propterty', function () {
    $valid = new Validator(['test' => 'test']);

    $valid->filter('test')->upper_case();

    expect($valid->filters)
        ->has('test')->toBeTrue()
        ->get('test')->toEqual('TEST')
    ;
});
