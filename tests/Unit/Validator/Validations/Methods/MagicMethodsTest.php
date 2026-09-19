<?php

use function Omega\Validator\vr;

it('can access unknown property using __get', function () {
    $valid = vr();

    expect($valid->__get('unknown_property'))->toBe($valid);
});

it('can call unknown method using __call', function () {
    $valid = vr();

    expect($valid->__call('unknown_method', []))->toBe($valid);
});

it('can render equals_field using __call')
    ->expect(vr()->equals_field('other'))
    ->toEqual('equalsfield,other')
;