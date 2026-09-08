<?php

use Omega\Validator\Validator;

it('can create static', function () {
    expect(Validator::make())->toBeInstanceOf(Validator::class);
});
