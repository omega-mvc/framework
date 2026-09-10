<?php

/**
 * Part of Omega - Tests\Time Package.
 *
 * @link      https://omega-mvc.github.io
 * @author    Adriano Giovannini <agisoftt@gmail.com>
 * @copyright Copyright (c) 2025 - 2026 Adriano Giovannini (https://omega-mvc.github.io)
 * @license   GPL-3.0-or-later
 * @version   2.0.0
 */

declare(strict_types=1);

namespace Tests\Time\Traits;

use Omega\Time\Now;
use Omega\Time\Traits\DateTimeFormatTrait;

covers(Now::class, DateTimeFormatTrait::class);

it('formats time in ATOM format', function (): void {
    $now = new Now('2026-03-02 15:30:45', 'UTC');

    expect($now->formatATOM())->toBe('2026-03-02T15:30:45+00:00');
});

it('formats time in COOKIE format with standard time', function (): void {
    $now = new Now('02-03-2026', 'UTC');

    expect($now->formatCOOKIE())->toEqual('Monday, 02-Mar-2026 00:00:00 UTC');
});

it('formats time in RFC822 format', function (): void {
    $now = new Now('2026-03-02 15:30:45', 'UTC');

    expect($now->formatRFC822())->toBe('Mon, 02 Mar 26 15:30:45 +0000');
});

it('formats time in RFC850 format', function (): void {
    $now = new Now('2026-03-02 15:30:45', 'UTC');

    expect($now->formatRFC850())->toBe('Monday, 02-Mar-26 15:30:45 UTC');
});

it('formats time in RFC1036 format', function (): void {
    $now = new Now('2026-03-02 15:30:45', 'UTC');

    expect($now->formatRFC1036())->toBe('Mon, 02 Mar 26 15:30:45 +0000');
});

it('formats time in RFC1123 format', function (): void {
    $now = new Now('2026-03-02 15:30:45', 'UTC');

    expect($now->formatRFC1123())->toBe('Mon, 02 Mar 2026 15:30:45 +0000');
});

it('formats time in RFC7231 format', function (): void {
    $now = new Now('2026-03-02 15:30:45', 'UTC');

    expect($now->formatRFC7231())->toBe('Mon, 02 Mar 2026 15:30:45 GMT');
});

it('formats time in RFC2822 format', function (): void {
    $now = new Now('2026-03-02 15:30:45', 'UTC');

    expect($now->formatRFC2822())->toBe('Mon, 02 Mar 2026 15:30:45 +0000');
});

it('formats time in RFC3339 format', function (): void {
    $now = new Now('2026-03-02 15:30:45', 'UTC');

    expect($now->formatRFC3339())->toBe('2026-03-02T15:30:45+00:00');
});

it('formats time in extended RFC3339 format with milliseconds', function (): void {
    $now = new Now('2026-03-02 15:30:45', 'UTC');

    expect($now->formatRFC3339(true))->toBe('2026-03-02T15:30:45.000+00:00');
});

it('formats time in RSS format', function (): void {
    $now = new Now('2026-03-02 15:30:45', 'UTC');

    expect($now->formatRSS())->toBe('Mon, 02 Mar 2026 15:30:45 +0000');
});

it('formats time in W3C format', function (): void {
    $now = new Now('2026-03-02 15:30:45', 'UTC');

    expect($now->formatW3C())->toBe('2026-03-02T15:30:45+00:00');
});