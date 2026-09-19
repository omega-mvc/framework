<?php

use function Omega\Validator\fr;

it('can rander whole_number', function () {
    expect(fr()->whole_number())
        ->toEqual('whole_number')
    ;
});

it('can filter whole_number', function () {
    $fr = new Omega\Validator\Validator(['field' => '123']);

    $fr->filter('field')->whole_number();

    expect($fr->filterOut())
        ->toEqual(['field' => 123])
    ;
});
