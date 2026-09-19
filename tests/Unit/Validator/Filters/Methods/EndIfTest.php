<?php

use Omega\Validator\Rule\Filter;
use ReflectionProperty;

it('can render end_if filter')
    ->expect(fn () => (new Filter())->if(fn (): bool => true)->end_if()->trim()->getFilter())
    ->toBe('trim')
;

it('can render both if and end_if filter')
    ->expect(fn () => (new Filter())->if(fn (): bool => false)->end_if()->trim()->getFilter())
    ->toBe('trim')
;

it('re-opens the filter rules block after an end_if')
    ->expect(fn () => (new Filter())->if(fn (): bool => false)->trim()->end_if()->upper_case()->getFilter())
    ->toBe('upper_case')
;

it('can directly append the end_if rule')
    ->expect((new Filter())->end_if()->getFilter())
    ->toBe('')
;

it('appends the end_if marker to the filter rules array')
    ->expect(fn () => (new ReflectionProperty(Filter::class, 'filter_rule'))
        ->getValue((new Filter())->end_if()))
    ->toBe(['end_if'])
;