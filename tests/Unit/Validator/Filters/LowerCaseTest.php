<?php

use function Omega\Validator\fr;

it('can rander lower_case', function () {
    expect(fr()->lower_case())
        ->toEqual('lower_case')
    ;
});

it('can filter lower_case', function () {
    $fr = new Omega\Validator\Validator(['field' => 'TEST']);

    $fr->filter('field')->lower_case();

    expect($fr->filterOut())
        ->toEqual(['field' => 'test'])
    ;
});
