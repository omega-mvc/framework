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

/** @noinspection PhpConditionAlreadyCheckedInspection */

declare(strict_types=1);

namespace Tests\Time;

use DateTimeImmutable;
use DateTimeZone;
use Omega\Time\Now;
use Omega\Time\Traits\DateTimeFormatTrait;

use function date;
use function date_default_timezone_set;
use function strtotime;

covers(Now::class, DateTimeFormatTrait::class, 'Omega\Time\now');

it('is same with current time', function (): void {
    $tz = new DateTimeZone(date_default_timezone_get());
    $timestamp = new DateTimeImmutable('now', $tz)->getTimestamp();

    $now = new Now('now', date_default_timezone_get());

    expect($now->getTimestamp())->toBe($timestamp);
    expect($now->getYear())->toBe((int) date('Y', $timestamp));
    expect($now->getMonth())->toBe((int) date('n', $timestamp));
    expect($now->getDay())->toBe((int) date('d', $timestamp));

    expect($now->getShortDay())->toBe(date('D', $timestamp));
    expect($now->getHour())->toBe((int) date('H', $timestamp));
    expect($now->getMinute())->toBe((int) date('i', $timestamp));
    expect($now->getSecond())->toBe((int) date('s', $timestamp));

    expect($now->getDayName())->toBe(date('l', $timestamp));
    expect($now->getMonthName())->toBe(date('F', $timestamp));

    expect($now->getDayOfWeek())->toBe((int) date('N', $timestamp));
});

it('is same with custom time', function (): void {
    date_default_timezone_set('UTC');

    $time = strtotime('2021-07-03 19:52:39');
    $now = new Now();
    $now = $now->setYear(2021)
        ->setMonth(7)
        ->setDay(3)
        ->setHour(19)
        ->setMinute(52)
        ->setSecond(39);

    expect($now->getYear())->toEqual((int) date('Y', $time));
    expect($now->getMonth())->toEqual((int) date('n', $time));
    expect($now->getDay())->toEqual((int) date('d', $time));
    expect($now->getShortDay())->toEqual(date('D', $time));
    expect($now->getHour())->toEqual((int) date('H', $time));
    expect($now->getMinute())->toEqual((int) date('i', $time));
    expect($now->getSecond())->toEqual((int) date('s', $time));
    expect($now->getDayName())->toEqual(date('l', $time));
    expect($now->getMonthName())->toEqual(date('F', $time));

    expect($now->isJul())->toBeTrue();
    expect($now->isSaturday())->toBeTrue();
});

it('calculates age correctly for typical birthday', function (): void {
    $today = new DateTimeImmutable('2026-03-01', new DateTimeZone('UTC'));

    $birthday = '1990-01-01';
    $now = new Now($birthday, 'UTC');

    $expectedAge = $today->diff(new DateTimeImmutable($birthday, new DateTimeZone('UTC')))->y;

    expect($now->getAge())->toBe($expectedAge);
});

it('handles leap year birthday correctly', function (): void {
    $today = new DateTimeImmutable('2026-03-01', new DateTimeZone('UTC'));

    $birthday = '2000-02-29';
    $now = new Now($birthday, 'UTC');

    $birthDate = new DateTimeImmutable($birthday, new DateTimeZone('UTC'));
    $expectedAge = $birthDate->diff($today)->y;

    expect($now->getAge())->toBe($expectedAge);
});

it('handles future birthdate correctly', function (): void {
    $future = new Now('+1 day');

    expect($future->getAge())->toBe(0);
});

it('calculates age as zero for today birthdate', function (): void {
    $todayUtc = new DateTimeImmutable('now', new DateTimeZone('UTC'));

    $now = new Now($todayUtc->format('Y-m-d H:i:s'), 'UTC');

    expect($now->getAge())->toBe(0);
});

it('handles edge cases around birthday correctly', function (): void {
    $yesterday = new DateTimeImmutable('yesterday', new DateTimeZone('UTC'));
    $nowBeforeBirthday = new Now($yesterday->format('Y-m-d'), 'UTC');
    $birthDateBefore   = new DateTimeImmutable($yesterday->format('Y-m-d'));
    $expectedAgeBefore = $birthDateBefore->diff(new DateTimeImmutable('now'))->y;

    expect($nowBeforeBirthday->getAge())->toBe($expectedAgeBefore);

    $tomorrow = new DateTimeImmutable('tomorrow', new DateTimeZone('UTC'));
    $nowAfterBirthday = new Now($tomorrow->format('Y-m-d'), 'UTC');
    $birthDateAfter   = new DateTimeImmutable($tomorrow->format('Y-m-d'));
    $expectedAgeAfter = $birthDateAfter->diff(new DateTimeImmutable('now'))->y;

    expect($nowAfterBirthday->getAge())->toBe($expectedAgeAfter);
});

it('handles different time zones correctly', function (): void {
    $utcZone = new DateTimeZone('UTC');
    $romeZone = new DateTimeZone('Europe/Rome');

    $nowUTC = new Now('2000-01-01', 'UTC');
    $nowRM  = new Now('2000-01-01', 'Europe/Rome');

    $birthDateUTC = new DateTimeImmutable('2000-01-01', $utcZone);
    $birthDateRM  = new DateTimeImmutable('2000-01-01', $romeZone);

    $expectedAgeUTC = $birthDateUTC->diff(new DateTimeImmutable('now', $utcZone))->y;
    $expectedAgeRM  = $birthDateRM->diff(new DateTimeImmutable('now', $romeZone))->y;

    expect($nowUTC->getAge())->toBe($expectedAgeUTC);
    expect($nowRM->getAge())->toBe($expectedAgeRM);
});

it('can get day from private property via setter', function (): void {
    $now = new Now();
    $nowUpdated = $now->setDay(12);

    expect($nowUpdated->getDay())->toEqual(12);
});

it('can set month via fluent method', function (): void {
    $now = new Now();
    $updatedNow = $now->setMonth(7);

    expect($updatedNow->getMonth())->toEqual(7);
});

it('can set year via fluent method', function (): void {
    $now = new Now();
    $updatedNow = $now->setYear(2025);

    expect($updatedNow->getYear())->toEqual(2025);
});

it('can use setters and getters', function (): void {
    $now = new Now();

    $now = $now->setYear(2022)
        ->setMonth(1)
        ->setDay(11)
        ->setHour(1)
        ->setMinute(27)
        ->setSecond(0);

    expect($now->getYear())->toEqual(2022);
    expect($now->getMonth())->toEqual(1);
    expect($now->getDay())->toEqual(11);
    expect($now->getHour())->toEqual(1);
    expect($now->getMinute())->toEqual(27);
    expect($now->getSecond())->toEqual(0);

    expect($now->getMonthName())->toEqual('January');
    expect($now->getDayName())->toEqual('Tuesday');
    expect($now->getShortDay())->toEqual('Tue');
    expect($now->getTimeZone())->toEqual('UTC');

    expect($now->getAge())->toBeLessThan(200);
    expect($now->getAge())->toBeGreaterThan(0);
});

it('can return formatted time', function (): void {
    $now = new Now('29-01-2023');

    expect($now->format('Y-m-d'))->toEqual('2023-01-29');
});

it('checks month helpers', function (int $month, string $expectedTrueMethod): void {
    $date = sprintf('2023-%02d-01', $month);
    $now = new Now($date, 'UTC');

    $allMethods = [
        1  => 'isJan',
        2  => 'isFeb',
        3  => 'isMar',
        4  => 'isApr',
        5  => 'isMay',
        6  => 'isJun',
        7  => 'isJul',
        8  => 'isAug',
        9  => 'isSep',
        10 => 'isOct',
        11 => 'isNov',
        12 => 'isDec',
    ];

    foreach ($allMethods as $method) {
        if ($method === $expectedTrueMethod) {
            expect($now->$method())->toBeTrue();
        } else {
            expect($now->$method())->toBeFalse();
        }
    }
})->with([
    [1, 'isJan'],
    [2, 'isFeb'],
    [3, 'isMar'],
    [4, 'isApr'],
    [5, 'isMay'],
    [6, 'isJun'],
    [7, 'isJul'],
    [8, 'isAug'],
    [9, 'isSep'],
    [10, 'isOct'],
    [11, 'isNov'],
    [12, 'isDec'],
]);

it('checks weekday helpers', function (string $date, string $expectedTrueMethod): void {
    $now = new Now($date, 'UTC');

    $allMethods = [
        'isMonday',
        'isTuesday',
        'isWednesday',
        'isThursday',
        'isFriday',
        'isSaturday',
        'isSunday',
    ];

    foreach ($allMethods as $method) {
        if ($method === $expectedTrueMethod) {
            expect($now->$method())->toBeTrue();
        } else {
            expect($now->$method())->toBeFalse();
        }
    }
})->with([
    ['2023-01-02', 'isMonday'],
    ['2023-01-03', 'isTuesday'],
    ['2023-01-04', 'isWednesday'],
    ['2023-01-05', 'isThursday'],
    ['2023-01-06', 'isFriday'],
    ['2023-01-07', 'isSaturday'],
    ['2023-01-08', 'isSunday'],
]);

it('converts to string', function (): void {
    $now = new Now('2023-03-01 15:30:45', 'UTC');

    expect($now->__toString())->toBe('2023-03-01T15:30:45');
});

it('converts to string direct call', function (): void {
    $now = new Now('2023-03-01 15:30:45', 'UTC');

    expect($now->__toString())->toBe('2023-03-01T15:30:45');
});

it('checks next helpers', function (string $unit, string $modify, bool $expected): void {
    $now = new Now($modify);

    $method = 'isNext' . ucfirst($unit);

    expect($now->$method())->toBe($expected);
})->with([
    ['year',   '+1 year',                  true],
    ['month',  'first day of next month',  true],
    ['day',    'tomorrow',                 true],
    ['hour',   '+1 hour',                  true],
    ['minute', '+1 minute',                true],
]);

it('checks last helpers', function (string $unit, string $modify, bool $expected): void {
    $now = new Now($modify);

    $method = 'isLast' . ucfirst($unit);

    expect($now->$method())->toBe($expected);
})->with([
    ['year',   '-1 year',                  true],
    ['month',  'first day of last month',  true],
    ['day',    'yesterday',                true],
    ['hour',   '-1 hour',                  true],
    ['minute', '-1 minute',                true],
]);