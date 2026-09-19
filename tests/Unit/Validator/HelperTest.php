<?php

declare(strict_types=1);

namespace Tests\Validator;

use Omega\Validator\Rule\Filter;
use Omega\Validator\Rule\Valid;
use Omega\Validator\Validator;

use function Omega\Validator\fr;
use function Omega\Validator\validate;
use function Omega\Validator\vr;


covers('Omega\Validator\vr');
covers('Omega\Validator\fr');
covers('Omega\Validator\validate');

it('vr helper creates a valid rule', function (): void {
    $instance = vr();

    expect($instance::class)->toBe(Valid::class);
});

it('fr helper creates a filter rule', function (): void {
    $instance = fr();

    expect($instance::class)->toBe(Filter::class);
});

it('validate helper creates a validator with the given fields', function (): void {
    $validator = validate(['name' => 'omega']);

    expect($validator::class)->toBe(Validator::class);
    expect($validator->getFields())->toBe(['name' => 'omega']);
});