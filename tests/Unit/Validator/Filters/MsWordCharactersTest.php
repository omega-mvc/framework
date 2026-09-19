<?php

use function Omega\Validator\fr;

it('can rander ms_word_characters', function () {
    expect(fr()->ms_word_characters())
        ->toEqual('ms_word_characters')
    ;
});

it('can filter ms_word_characters', function () {
    $fr = new Omega\Validator\Validator(['field' => '“test”,‘test’,–,…']);

    $fr->filter('field')->ms_word_characters();

    expect($fr->filterOut())
        ->toEqual(['field' => '"test",\'test\',-,...'])
    ;
});
