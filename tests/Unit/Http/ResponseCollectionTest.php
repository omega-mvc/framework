<?php

declare(strict_types=1);

namespace Tests\Http;

use Exception;
use Omega\Http\HeaderCollection;
use Omega\Text\Str;
use Throwable;

covers(HeaderCollection::class);
covers(Str::class);

it('can generate header to header string', function (): void {
    $header = new HeaderCollection([
        'Host'       => 'test.test',
        'Accept'     => 'text/html',
        'Connection' => 'keep-alive',
    ]);

    expect(Str::contains((string) $header, 'Host: test.test'))->toBeTrue();
    expect(Str::contains((string) $header, 'Accept: text/htm'))->toBeTrue();
    expect(Str::contains((string) $header, 'Connection: keep-alive'))->toBeTrue();
});

it('can generate header using set with value', function (): void {
    $header = new HeaderCollection([]);
    $header->set('Host', 'test.test');
    $header->set('Accept', 'text/html');
    $header->set('Connection', 'keep-alive');

    expect(Str::contains((string) $header, 'Host: test.test'))->toBeTrue();
    expect(Str::contains((string) $header, 'Accept: text/htm'))->toBeTrue();
    expect(Str::contains((string) $header, 'Connection: keep-alive'))->toBeTrue();
});

it('can generate header using set with key only', function (): void {
    $header = new HeaderCollection([]);
    $header->setRaw('Host: test.test');
    $header->setRaw('Accept: text/html');
    $header->setRaw('Connection: keep-alive');

    expect(Str::contains((string) $header, 'Host: test.test'))->toBeTrue();
    expect(Str::contains((string) $header, 'Accept: text/htm'))->toBeTrue();
    expect(Str::contains((string) $header, 'Connection: keep-alive'))->toBeTrue();
});

it('can generate header using set with key only but throw error', function (): void {
    $header  = new HeaderCollection([]);
    $message = '';
    try {
        $header->setRaw('Host=test.test');
    } catch (Throwable $th) {
        $message = $th->getMessage();
    }

    expect($message)->toEqual('Invalid header structure Host=test.test.');
});