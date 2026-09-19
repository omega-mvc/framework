<?php

use Omega\Validator\Rule\Valid;
use ReflectionProperty;

use function Omega\Validator\vr;

it('can render end_if validation')
    ->expect(vr()->if(fn (): bool => true)->end_if()->required())
    ->toEqual('required')
;

it('can render both if and end_if validation')
    ->expect(vr()->not()->if(fn (): bool => false)->end_if()->required())
    ->toEqual('invert_required')
;

it('can directly append the end_if rule')
    ->expect((new Valid())->end_if()->getValidation())
    ->toBe('')
;

it('appends the end_if marker to the validation rules array')
    ->expect(fn () => (new ReflectionProperty(Valid::class, 'validation_rule'))
        ->getValue((new Valid())->end_if()))
    ->toBe(['end_if'])
;