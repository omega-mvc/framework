<?php

declare(strict_types=1);

namespace Tests\Collection;

use Omega\Collection\Collection;

use function Omega\Collection\data_get;

covers(Collection::class);

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
