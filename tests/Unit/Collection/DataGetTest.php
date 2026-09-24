<?php

declare(strict_types=1);

namespace Tests\Collection;

use function Omega\Collection\data_get;

covers('Omega\Collection\data_get');

$array = [
    'awesome'   => [
        'lang' => [
            'go',
            'rust',
            'php',
            'python',
            'js',
        ],
    ],
    'fav'       => [
        'lang' => [
            'rust',
            'php',
        ],
    ],
    'dont_know' => ['lang_' => ['back_end' => ['erlang', 'h-lang']]],
    'one'       => ['two' => ['three' => ['four' => ['five' => 6]]]],
];

it('can find item using dot keys', function () use ($array): void {
    expect(data_get($array, 'one.two.three.four.five'))->toEqual(6);
});

it('can find item using dot keys but key does not exist', function () use ($array): void {
    expect(data_get($array, '1.2.3.4.5', 'six'))->toEqual('six');
});

it('can find items using dot key with wildcard', function () use ($array): void {
    expect(data_get($array, '*.lang'))->toEqual([
        ['go', 'rust', 'php', 'python', 'js'],
        ['rust', 'php'],
    ]);
});

it('can get keys as integer', function (): void {
    $array = ['foo', 'bar', 'baz'];

    expect(data_get($array, 1))->toEqual('bar');
    expect(data_get($array, 3))->toBeNull();
    expect(data_get($array, 3, 'qux'))->toEqual('qux');
});

it('returns the default when the key path traverses a non-array value', function () use ($array): void {
    expect(data_get($array, 'one.two.three.four.five.six', 'missing'))->toBe('missing');
});

it('returns the default when a wildcard matches no values', function (): void {
    expect(data_get(['a' => 1, 'b' => 2], '*.x', 'none'))->toBe('none');
});

it('returns the default when a wildcard segment traverses an empty array', function (): void {
    expect(data_get([], '*.x', 'none'))->toBe('none');
    expect(data_get(['a' => []], 'a.*.x', 'none'))->toBe('none');
});

it('keeps non-null wildcard matches and drops null ones', function (): void {
    expect(data_get([
        'a' => ['x' => 1],
        'b' => ['y' => 2],
        'c' => ['x' => 3],
    ], '*.x'))->toEqual([1, 3]);
});

it('can find a single-segment key', function () use ($array): void {
    expect(data_get($array, 'dont_know'))->toEqual(['lang_' => ['back_end' => ['erlang', 'h-lang']]]);
});

it('returns the default for a missing single-segment key', function () use ($array): void {
    expect(data_get($array, 'missing', 'fallback'))->toBe('fallback');
});

it('can find a two-segment key', function () use ($array): void {
    expect(data_get($array, 'one.two'))->toEqual(['three' => ['four' => ['five' => 6]]]);
});

it('returns the default when a short path traverses a non-array value', function (): void {
    expect(data_get(['a' => ['b' => 'plain']], 'a.b.c', 'd'))->toBe('d');
});

it('wildcard matches a single item', function (): void {
    expect(data_get(['a' => ['lang' => 'x']], '*.lang'))->toEqual(['x']);
});

it('wildcard matches three items', function (): void {
    expect(data_get([
        'a' => ['lang' => 'x'],
        'b' => ['lang' => 'y'],
        'c' => ['lang' => 'z'],
    ], '*.lang'))->toEqual(['x', 'y', 'z']);
});

it('returns the default when a wildcard drops every null value', function (): void {
    expect(data_get(['a' => ['y' => 1]], '*.x', 'none'))->toBe('none');
});

it('wildcard works mid-path', function (): void {
    expect(data_get(['a' => ['l1' => ['x' => 1], 'l2' => ['x' => 2]]], 'a.*.x'))->toEqual([1, 2]);
});

it('returns the default for an empty string key', function () use ($array): void {
    expect(data_get($array, '', 'none'))->toBe('none');
    expect(data_get([], '', 'none'))->toBe('none');
});
