<?php

use Omega\Collection\Collection;

it('count total items in collection', function () {
    expect(new Collection([
        'key_1' => 'item_1',
        'key_2' => 'item_2',
        'key_3' => 'item_3',
        'key_4' => 'item_4',
        'key_5' => 'item_5',
    ]))->count(5)->toEqual(5);
});

it('count total items in collection (null)', function () {
    expect(new Collection([]))->count(0)->toEqual(0);
});