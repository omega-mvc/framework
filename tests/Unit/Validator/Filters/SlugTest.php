<?php

use function Omega\Validator\fr;

it('can rander slug', function () {
    expect(fr()->slug())
        ->toEqual('slug')
    ;
});

it('can filter slug', function () {
    $fr = new Omega\Validator\Validator(['field' => 'long title tobe url']);

    $fr->filter('field')->slug();

    expect($fr->filterOut())
        ->toEqual(['field' => 'long-title-tobe-url'])
    ;
});
