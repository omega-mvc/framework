<?php

declare(strict_types=1);

namespace Tests\Testing;

use LogicException;
use Omega\Http\Response;
use Omega\Testing\TestResponse;
use Omega\Testing\Traits\ResponseStatusTrait;

covers(Response::class);
covers(TestResponse::class);
covers(ResponseStatusTrait::class);

it('can respond assert', function (): void {
    $response = new TestResponse(new Response('test', 200, []));

    expect($response->getContent())->toBe('test');
    $response->assertSee('test');
    $response->assertStatusCode(200);
});

it('checks offset exists and get', function (): void {
    $response = new TestResponse(new Response(['foo' => 'bar', 'baz' => 123], 200, []));

    expect(isset($response['foo']))->toBeTrue();
    expect(isset($response['nonexistent']))->toBeFalse();

    expect($response['foo'])->toBe('bar');
    expect($response['baz'])->toBe(123);
    expect($response['nonexistent'])->toBeNull();
});

it('throws logic exception on offset set', function (): void {
    $response = new TestResponse(new Response(['foo' => 'bar'], 200, []));

    expect(fn () => $response['foo'] = 'new value')
        ->toThrow(LogicException::class, 'TestResponse is read-only.');
});

it('throws logic exception on offset unset', function (): void {
    $response = new TestResponse(new Response(['foo' => 'bar'], 200, []));

    expect(function () use ($response): void {
        unset($response['foo']);
    })->toThrow(LogicException::class, 'TestResponse is read-only.');
});

it('returns the original response', function (): void {
    $original = new Response(['hello' => 'world'], 200, []);
    $response = new TestResponse($original);

    expect($response->getResponse())->toBe($original);
});

it('handles invalid json or non array content', function (): void {
    $response = new TestResponse(new Response('non-json', 200, []));

    expect($response['nonexistent'] ?? [])->toBeArray();
    expect($response['nonexistent'])->toBeNull();
});

it('gets content with array content', function (): void {
    $response = new TestResponse(new Response(['foo' => 'bar'], 200, []));

    expect($response->getContent())->toContain('"foo":"bar"');
});

it('sets empty decoded array for invalid json', function (): void {
    $response = new TestResponse(new Response('invalid-json', 200, []));

    expect(isset($response['anything']))->toBeFalse();
    expect($response['anything'])->toBeNull();
});

it('decodes a valid json string', function (): void {
    $response = new TestResponse(new Response('{"foo":"bar"}', 200, []));

    expect(isset($response['foo']))->toBeTrue();
    expect($response['foo'])->toBe('bar');
});