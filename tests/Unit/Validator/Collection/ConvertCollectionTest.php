<?php

use Omega\Collection\Collection;

it('can convert to array', function () {
    $array = ['key' => 'item'];

    expect(new Collection($array))
        ->all()->toEqual($array);
});