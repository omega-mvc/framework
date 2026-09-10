<?php

use Omega\Collection\Collection;

it('can check item with exist item', function () {
    expect(new Collection(['key' => 'item']))
        ->has('key')->toBeTrue();
});

it('can check item with not exist item', function () {
    expect(new Collection([]))
        ->has('key')->toBeFalse();
});