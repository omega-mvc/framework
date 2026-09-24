<?php

/**
 * Part of Omega - Tests\Text Package.
 *
 * @link      https://omega-mvc.github.io
 * @author    Adriano Giovannini <agisoftt@gmail.com>
 * @copyright Copyright (c) 2025 - 2026 Adriano Giovannini (https://omega-mvc.github.io)
 * @license   https://www.gnu.org/licenses/gpl-3.0-standalone.html     GPL V3.0+
 * @version   2.0.0
 */

declare(strict_types=1);

namespace Tests\Text;

use Omega\Text\Text;

use function Omega\Text\string;
use function Omega\Text\text;

use function expect;

covers('Omega\Text\string');
covers(Text::class);

it('creates a Text instance with string helper', function (): void {
    $instance = string('hello');

    expect($instance::class)->toBe(Text::class);
});

it('string helper wraps the given string', function (): void {
    expect((string) string('hello'))->toBe('hello');
});

it('string helper is fluent', function (): void {
    expect((string) string('hello')->upper())->toBe('HELLO');
});

it('creates a Text instance with text helper', function (): void {
    $instance = text('hello');

    expect($instance::class)->toBe(Text::class);
})->coversFunction('Omega\Text\text');

it('text helper wraps the given string', function (): void {
    expect((string) text('hello'))->toBe('hello');
})->coversFunction('Omega\Text\text');

it('text helper is fluent', function (): void {
    expect((string) text('hello')->upper())->toBe('HELLO');
})->coversFunction('Omega\Text\text');