<?php

declare(strict_types=1);

namespace Tests\Http;

use Omega\Http\Exceptions\StreamedResponseCallableException;
use Omega\Http\Request;
use Omega\Http\StreamedResponse;
use PHPUnit\Framework\Attributes\CoversClass;
use Tests\TestCase;

#[CoversClass(StreamedResponseCallableException::class)]
#[CoversClass(Request::class)]
#[CoversClass(StreamedResponse::class)]
final class StreamedResponseTest extends TestCase
{
    public function testCanUseConstructor(): void
    {
        $response = new StreamedResponse(function () {
            echo 'php';
        }, 200, ['Content-Type' => 'text/plain']);
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('text/plain', $response->getHeaders()['Content-Type']);
    }

    public function testCanCreateStreamResponseUsingRequest(): void
    {
        $response = new StreamedResponse(function () {
            echo 'php';
        }, 200, ['Content-Type' => 'application/json']);
        $request  = new Request('', [], [], [], [], [], ['Content-Type' => 'text/plain'], 'HEAD');
        $response->followRequest($request, ['Content-Type']);

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('text/plain', $response->getHeaders()['Content-Type']);
    }

    public function testCanSendContent(): void
    {
        $called = 0;

        $response = new StreamedResponse(function () use (&$called) {
            $called++;
        });

        (fn () => $this->{'sendContent'}())->call($response);
        $this->assertEquals(1, $called);

        (fn () => $this->{'sendContent'}())->call($response);
        $this->assertEquals(1, $called);
    }

    public function testCanSendContentWithNonCallable(): void
    {
        $this->expectException(StreamedResponseCallableException::class);
        $response = new StreamedResponse(null);
        (fn () => $this->{'sendContent'}())->call($response);
    }
}
