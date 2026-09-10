<?php

declare(strict_types=1);

namespace Tests\Text;

use Omega\Macroable\Exceptions\MacroNotFoundException;
use Omega\Text\Str;

use function expect;

covers(MacroNotFoundException::class);
covers(Str::class);

it('can register string macro', function (): void {
    Str::macro('addPrefix', fn (string $text, string $prefix) => $prefix . $text);

    expect(Str::addPrefix('laravel', 'i love '))->toBe('i love laravel');

    Str::resetMacro();
});

it('can throw error when macro not found', function (): void {
    Str::hay();
})->throws(MacroNotFoundException::class);

it('can reset string macro', function (): void {
    Str::macro('addPrefix', fn (string $text, string $prefix) => $prefix . $text);

    $addPrefix = Str::addPrefix('a', 'b');
    expect($addPrefix)->toBe('ba');
    Str::resetMacro();

    Str::addPrefix('a', 'b');
    Str::resetMacro();
})->throws(MacroNotFoundException::class);
