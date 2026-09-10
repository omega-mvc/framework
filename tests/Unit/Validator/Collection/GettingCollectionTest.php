<?php

use Omega\Collection\Collection;

it('can get item using get', function () {
    $collection = new Collection(['key' => 'item']);

    expect($collection)->get('key')->toEqual('item');
});

it('can get item using get (default) but not exist', function () {
    /** @var Collection<string, string> $collection */
    $collection = new Collection([]);

    expect($collection)->get('key', 'no item')->toEqual('no item');
});

it('can get item using get (no default set) but not exist', function () {
    /** @var Collection<string, string> $collection */
    $collection = new Collection([]);

    expect($collection)->get('key')->toBeNull();
});

it('can get item using __get', function () {
    $collection = new Collection(['key' => 'item']);

    expect($collection)->key->toEqual('item');
});

it('can get item using __get (no default set) but not exist', function () {
    /** @var Collection<string, string> $collection */
    $collection = new Collection(['seed' => 'value']);

    expect($collection)->item->toBeNull();
});