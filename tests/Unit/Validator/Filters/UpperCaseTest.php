<?php

use function Omega\Validator\fr;

it('can rander upper_case', function () {
    expect(fr()->upper_case())
        ->toEqual('upper_case')
    ;
});

it('can filter upper_case', function () {
    $fr = new Omega\Validator\Validator(['field' => 'test']);

    $fr->filter('field')->upper_case();

    expect($fr->filterOut())
        ->toEqual(['field' => 'TEST'])
    ;
});
