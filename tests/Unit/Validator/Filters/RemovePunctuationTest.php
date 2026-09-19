<?php

use function Omega\Validator\fr;

it('can rander rmpunctuation', function () {
    expect(fr()->rmpunctuation())
        ->toEqual('rmpunctuation')
    ;
});

it('can filter rmpunctuation', function () {
    $fr = new Omega\Validator\Validator(['field' => 'is true?']);

    $fr->filter('field')->rmpunctuation();

    expect($fr->filterOut())
        ->toEqual(['field' => 'is true'])
    ;
});
