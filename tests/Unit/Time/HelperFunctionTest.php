<?php

/**
 * Part of Omega - Tests\Time Package.
 *
 * @link      https://omega-mvc.github.io
 * @author    Adriano Giovannini <agisoftt@gmail.com>
 * @copyright Copyright (c) 2025 - 2026 Adriano Giovannini (https://omega-mvc.github.io)
 * @license   https://www.gnu.org/licenses/gpl-3.0-standalone.html     GPL V3.0+
 * @version   2.0.0
 */

declare(strict_types=1);

namespace Tests\Time;

use DateInvalidTimeZoneException;
use DateMalformedStringException;
use Omega\Time\Now;

use function Omega\Time\now;
use function date_default_timezone_get;
use function strtotime;

covers('Omega\Time\now');

it('returns a Now instance with current time by default', function (): void {
    $instance = now();

    expect($instance::class)->toBe(Now::class);
});

it('uses current date and default timezone', function (): void {
    $instance = now('now');

    expect($instance->getTimeZone())->toEqual(date_default_timezone_get());
    expect($instance->getTimestamp())->toBeBetween((int) strtotime('-1 minute'), (int) strtotime('+1 minute'));
});

it('accepts a custom date', function (): void {
    $instance = now('2023-01-29');

    expect($instance->getYear())->toBe(2023);
    expect($instance->getMonth())->toBe(1);
    expect($instance->getDay())->toBe(29);
});

it('accepts a custom date and timezone', function (): void {
    $instance = now('2023-01-29', 'Europe/Rome');

    expect($instance->getYear())->toBe(2023);
    expect($instance->getTimeZone())->toBe('Europe/Rome');
});

it('throws DateMalformedStringException with an invalid date', function (): void {
    now('not-a-date');
})->throws(DateMalformedStringException::class);

it('throws DateInvalidTimeZoneException with an invalid timezone', function (): void {
    now('2023-01-29', 'Not/AZone');
})->throws(DateInvalidTimeZoneException::class);