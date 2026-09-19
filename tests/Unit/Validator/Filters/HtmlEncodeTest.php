<?php

use function Omega\Validator\fr;

it('can rander htmlencode', function () {
    expect(fr()->htmlencode())
        ->toEqual('htmlencode')
    ;
});

it('can filter htmlencode', function () {
    $fr = new Omega\Validator\Validator(['field' => '<html>html tag</html>']);

    $fr->filter('field')->htmlencode();

    expect($fr->filterOut())
        ->toEqual(['field' => '&#60;html&#62;html tag&#60;/html&#62;'])
    ;
});
