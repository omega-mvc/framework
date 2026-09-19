<?php

use function Omega\Validator\fr;

it('can rander sanitize_floats', function () {
    expect(fr()->sanitize_floats())
        ->toEqual('sanitize_floats')
    ;
});

it('can filter sanitize_floats', function () {
    $fr = new Omega\Validator\Validator(['field' => '12.3']);

    $fr->filter('field')->sanitize_floats();

    expect($fr->filterOut())
        ->toEqual(['field' => 12.3])
    ;
});
