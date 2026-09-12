<?php

declare(strict_types=1);

namespace Tests\Http;

use Omega\Http\Exceptions\StreamedResponseCallableException;
use Omega\Http\Request;
use Omega\Http\StreamedResponse;

covers(StreamedResponseCallableException::class);
covers(Request::class);
covers(StreamedResponse::class);

it('can use constructor', function (): void {
    $response = new StreamedResponse(function () {
        echo 'php';
    }, 200, ['Content-Type' => 'text/plain']);
    expect($response->getStatusCode())->toEqual(200);
    expect($response->getHeaders()['Content-Type'])->toEqual('text/plain');
});

it('can create stream response using request', function (): void {
    $response = new StreamedResponse(function () {
        echo 'php';
    }, 200, ['Content-Type' => 'application/json']);
    $request  = new Request('', [], [], [], [], [], ['Content-Type' => 'text/plain'], 'HEAD');
    $response->followRequest($request, ['Content-Type']);

    expect($response->getStatusCode())->toEqual(200);
    expect($response->getHeaders()['Content-Type'])->toEqual('text/plain');
});

it('can send content', function (): void {
    $called = 0;

    $response = new StreamedResponse(function () use (&$called) {
        $called++;
    });

    (fn () => $this->{'sendContent'}())->call($response);
    expect($called)->toEqual(1);

    (fn () => $this->{'sendContent'}())->call($response);
    expect($called)->toEqual(1);
});

it('can send content with non callable', function (): void {
    $this->expectException(StreamedResponseCallableException::class);
    $response = new StreamedResponse(null);
    (fn () => $this->{'sendContent'}())->call($response);
});