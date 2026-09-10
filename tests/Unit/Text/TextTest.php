<?php

declare(strict_types=1);

namespace Tests\Text;

use Omega\Text\Str;
use Omega\Text\Text;

use function expect;
use function Omega\Text\string;
use function Omega\Text\text;

covers(Str::class);
covers(Text::class);

it('can create new instance using helper', function (): void {
    expect(string('text')->getText())->toBe('text');
    expect(text('text')->getText())->toBe('text');
});

it('can create new instance using str class', function (): void {
    expect(Str::of('text')->getText())->toBe('text');
});

it('can set get current text', function (): void {
    $class = new Text('text');

    expect($class->getText())->toBe('text');
});

it('can set get current text using to string', function (): void {
    $class = new Text('text');

    expect($class)->toEqual('text');
});

it('can set new text without reset', function (): void {
    $class = new Text('text');
    $class->upper()->lower()->firstUpper();
    $class->text('string');

    expect($class->getText())->toBe('string');
    expect($class->logs())->toHaveCount(5);
});

it('can set get log of string', function (): void {
    $class = new Text('text');
    $class->upper()->lower()->firstUpper();

    $logs = $class->logs();
    expect($logs)->toHaveCount(4);

    foreach ($logs as $log) {
        expect($log)->toHaveKeys(['function', 'return', 'type']);
    }
});

it('can set reset', function (): void {
    $class = new Text('text');
    $class->upper()->lower()->firstUpper();
    $class->reset();

    expect($class->getText())->toBe('text');
    expect($class->logs())->toBeEmpty();
});

it('can set refresh', function (): void {
    $class = new Text('text');
    $class->upper()->lower()->firstUpper();
    $class->refresh('string');

    expect($class->getText())->toBe('string');
    expect($class->logs())->toBeEmpty();
});

it('can chain non string and continue chain without break', function (): void {
    $class = new Text('text');
    $class->upper()->firstUpper();

    expect($class->startsWith('T'))->toBeTrue();
    expect($class->length())->toBe(4);

    $class->lower();
    expect($class->startsWith('t'))->toBeTrue();
});
