<?php

use function Omega\Validator\fr;

it('can rander trim', function () {
    expect(fr()->trim())
        ->toEqual('trim')
    ;
});

it('can filter trim', function () {
    $fr = new Omega\Validator\Validator(['field' => '  nomore space  ']);

    $fr->filter('field')->trim();

    expect($fr->filterOut())
        ->toEqual(['field' => 'nomore space'])
    ;
});
