<?php

declare(strict_types=1);

namespace Tests\Testing;

use Exception;
use Omega\Http\Response;
use Omega\Testing\TestJsonResponse;

covers(Response::class);
covers(TestJsonResponse::class);

it('allows array access to response data', function (): void {
    $response = new TestJsonResponse(new Response([
        'status' => 'ok',
        'code'  => 200,
        'data'  => [
            'test' => 'success',
        ],
        'error' => null,
    ]));
    $response['test'] = 'test';

    expect($response['status'])->toBe('ok');
    expect($response['test'])->toBe('test');
});

it('returns the data part of the response', function (): void {
    $response = new TestJsonResponse(new Response([
        'status' => 'ok',
        'code'  => 200,
        'data'  => [
            'test' => 'success',
        ],
        'error' => null,
    ]));

    expect($response->getData())->toEqual(['test' => 'success']);
    expect($response['status'])->toBe('ok');
});

it('asserts that a data key equals the expected value', function (): void {
    $response = new TestJsonResponse(new Response([
        'status' => 'ok',
        'code'  => 200,
        'data'  => [
            'test' => 'success',
        ],
        'error' => null,
    ]));

    $response->assertEqual('data.test', 'success');
});

it('asserts that a data key is true', function (): void {
    $response = new TestJsonResponse(new Response([
        'status' => 'ok',
        'code'  => 200,
        'data'  => [
            'test' => true,
        ],
        'error' => null,
    ]));

    $response->assertTrue('data.test');
});

it('asserts that a data key is false', function (): void {
    $response = new TestJsonResponse(new Response([
        'status' => 'ok',
        'code'  => 200,
        'data'  => [
            'test' => false,
        ],
        'error' => null,
    ]));

    $response->assertFalse('data.test');
});

it('asserts that a data key is null', function (): void {
    $response = new TestJsonResponse(new Response([
        'status' => 'ok',
        'code'  => 200,
        'data'  => [
            'test' => false,
        ],
        'error' => null,
    ]));

    $response->assertNull('error');
});

it('asserts that a data key is not null', function (): void {
    $response = new TestJsonResponse(new Response([
        'status' => 'ok',
        'code'  => 200,
        'data'  => [
            'test' => false,
        ],
        'error' => [
            'test' => 'some error',
        ],
    ]));

    $response->assertNotNull('error');
});

it('asserts that the data payload is empty', function (): void {
    $response = new TestJsonResponse(new Response([
        'status' => 'ok',
        'code'  => 200,
        'data'  => [],
        'error' => null,
    ]));

    $response->assertEmpty('error');
});

it('asserts that the data payload is not empty', function (): void {
    $response = new TestJsonResponse(new Response([
        'status' => 'ok',
        'code'  => 200,
        'data'  => [
            'test' => false,
        ],
        'error' => null,
    ]));

    $response->assertNotEmpty('error');
});

it('throws when the response content is not an array', function (): void {
    expect(fn () => new TestJsonResponse(new Response('not-an-array')))
        ->toThrow(Exception::class, 'Response body is not Array.');
});

it('replaces the response data', function (): void {
    $response = new TestJsonResponse(new Response([
        'data' => [],
    ]));

    $response->setResponseData([
        'data' => ['changed' => true],
    ]);

    expect($response->getData())->toEqual(['changed' => true]);
});

it('checks whether an offset exists', function (): void {
    $response = new TestJsonResponse(new Response([
        'status' => 'ok',
        'data' => [],
    ]));

    expect(isset($response['status']))->toBeTrue();
    expect(isset($response['missing']))->toBeFalse();
});

it('unsets an offset', function (): void {
    $response = new TestJsonResponse(new Response([
        'status' => 'ok',
        'data' => [],
    ]));

    unset($response['status']);

    expect(isset($response['status']))->toBeFalse();
});

it('returns false when the offset is not a string or int', function (): void {
    $response = new TestJsonResponse(new Response([
        'status' => 'ok',
        'data' => [],
    ]));

    expect($response->offsetExists(1.5))->toBeFalse();
});

it('returns null when the offset is not a string or int', function (): void {
    $response = new TestJsonResponse(new Response([
        'status' => 'ok',
        'data' => [],
    ]));

    expect($response->offsetGet(1.5))->toBeNull();
});

it('does not set the value when the offset is not a string or int', function (): void {
    $response = new TestJsonResponse(new Response([
        'status' => 'ok',
        'data' => [],
    ]));

    $response->offsetSet(1.5, 'ignored');

    expect($response->offsetExists('status'))->toBeTrue();
    expect($response->offsetExists('ignored'))->toBeFalse();
});

it('does not unset the value when the offset is not a string or int', function (): void {
    $response = new TestJsonResponse(new Response([
        'status' => 'ok',
        'data' => [],
    ]));

    $response->offsetUnset(1.5);

    expect($response->offsetExists('status'))->toBeTrue();
});

it('supports integer offsets', function (): void {
    $response = new TestJsonResponse(new Response([
        0     => 'zero',
        'var' => 'ok',
    ]));

    expect($response->offsetExists(0))->toBeTrue();

    expect($response->offsetGet(0))->toBe('zero');

    $response->offsetSet(0, 'changed');

    expect($response->offsetGet(0))->toBe('changed');

    $response->offsetUnset(0);

    expect($response->offsetExists(0))->toBeFalse();
});
