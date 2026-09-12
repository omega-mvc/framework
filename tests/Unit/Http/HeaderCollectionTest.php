<?php

declare(strict_types=1);

namespace Tests\Http;

use Exception;
use Omega\Http\HeaderCollection;

covers(HeaderCollection::class);

it('can get string of header', function (): void {
    $header = new HeaderCollection([
        'Cache-Control' => 'max-age=31536000, public, no-transform, proxy-revalidate, s-maxage=2592000',
    ]);
    expect((string) $header)->toEqual(
        'Cache-Control: max-age=31536000, public, no-transform, proxy-revalidate, s-maxage=2592000'
    );

    // with multi value
    $header = new HeaderCollection([
        'Cache-Control' => 'no-cache="http://example.com, http://example2.com"',
    ]);
    expect((string) $header)->toEqual(
        'Cache-Control: no-cache="http://example.com, http://example2.com"'
    );
});

it('can add raw header', function (): void {
    $header = new HeaderCollection([]);
    $header->setRaw('Cache-Control: max-age=31536000, public, no-transform, proxy-revalidate, s-maxage=2592000');
    expect((string) $header)->toEqual(
        'Cache-Control: max-age=31536000, public, no-transform, proxy-revalidate, s-maxage=2592000'
    );
});

it('can get header item directly', function (): void {
    $header = new HeaderCollection([
        'Cache-Control' => 'max-age=31536000, public, no-transform, proxy-revalidate, s-maxage=2592000',
    ]);

    expect($header->getDirective('Cache-Control'))->toEqual([
        'max-age' => '31536000',
        'public',
        'no-transform',
        'proxy-revalidate',
        's-maxage' => '2592000',
    ]);
});

it('can get header item directly multi value', function (): void {
    $header = new HeaderCollection([
        'Cache-Control' => 'no-cache="http://example.com, http://example2.com"',
    ]);

    expect($header->getDirective('Cache-Control'))->toEqual([
        'no-cache' => [
            'http://example.com',
            'http://example2.com',
        ],
    ]);
});

it('can add header item directly', function (): void {
    $header = new HeaderCollection([
        'Cache-Control' => 'max-age=31536000, public, no-transform',
    ]);
    $header->addDirective('Cache-Control', ['proxy-revalidate', 's-maxage' => '2592000']);

    expect($header->getDirective('Cache-Control'))->toEqual([
        'max-age' => '31536000',
        'public',
        'no-transform',
        'proxy-revalidate',
        's-maxage' => '2592000',
    ]);
});

it('can remove header item directly', function (): void {
    $header = new HeaderCollection([
        'Cache-Control' => 'max-age=31536000, public, no-transform, proxy-revalidate, s-maxage=2592000',
    ]);
    $header->removeDirective('Cache-Control', 's-maxage');
    $header->removeDirective('Cache-Control', 'public');

    expect($header->getDirective('Cache-Control'))->toEqual([
        'max-age' => '31536000',
        'no-transform',
        'proxy-revalidate',
    ]);
});

it('can check header item directly', function (): void {
    $header = new HeaderCollection([
        'Cache-Control' => 'max-age=31536000, public, no-transform, proxy-revalidate, s-maxage=2592000',
    ]);

    expect($header->hasDirective('Cache-Control', 'proxy-revalidate'))->toBeTrue();
    expect($header->hasDirective('Cache-Control', 's-maxage'))->toBeTrue();
    expect($header->hasDirective('Cache-Control', 'private'))->toBeFalse();
});