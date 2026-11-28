<?php

declare(strict_types=1);

namespace Tests\Http;

use InvalidArgumentException;
use Omega\Http\Http;
use Omega\Http\Request;
use Omega\Http\Response;
use Omega\Http\RoadRunnerResponderInterface;
use Omega\Http\RoadRunnerWorker;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Http\Message\StreamInterface;
use Psr\Http\Message\UriInterface;
use ReflectionClass;
use RuntimeException;
use Tests\TestCase;

#[CoversClass(RoadRunnerWorker::class)]
final class RoadRunnerWorkerCoverageTest extends TestCase
{
    #[AllowMockObjectsWithoutExpectations]
    public function testConstructorRequiresFactoriesWithoutCustomResponseFactory(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('When no custom responseFactory is provided');

        new RoadRunnerWorker($this->createMock(Http::class));
    }

    #[AllowMockObjectsWithoutExpectations]
    public function testRunStopsWhenNoRequestIsAvailable(): void
    {
        $http = $this->createMock(Http::class);
        $http->expects($this->never())->method('handle');

        $responder = $this->responder([null]);

        (new RoadRunnerWorker($http, $this->factory()))->run($responder);
    }

    #[AllowMockObjectsWithoutExpectations]
    public function testRunStopsImmediatelyWhenWorkerIsStopped(): void
    {
        $http = $this->createMock(Http::class);
        $http->expects($this->never())->method('handle');

        (new RoadRunnerWorker($http, $this->factory()))->run($this->responder([false]));
    }

    public function testRunHandlesRequestSendsResponseAndTerminates(): void
    {
        $captured = null;
        $http     = $this->createMock(Http::class);
        $http->method('handle')->willReturn(new Response('ok', 200));
        $http->expects($this->once())
            ->method('terminate')
            ->with($this->isInstanceOf(Request::class), $this->isInstanceOf(Response::class));

        $responder = $this->responder([$this->serverRequest(), false]);
        $responder->expects($this->once())->method('respond');

        (new RoadRunnerWorker($http, $this->factory($captured)))->run($responder);

        $this->assertInstanceOf(Response::class, $captured);
        $this->assertSame('ok', $captured->getContent());
    }

    public function testRunTerminatesThenStopsWhenNoRequestIsAvailable(): void
    {
        $http = $this->createMock(Http::class);
        $http->method('handle')->willReturn(new Response('ok', 200));
        $http->expects($this->once())->method('terminate');

        $responder = $this->responder([$this->serverRequest(), null]);
        $responder->expects($this->once())->method('respond');

        (new RoadRunnerWorker($http, $this->factory()))->run($responder);
    }

    public function testRunContinuesAfterFailedConversionUntilNoRequest(): void
    {
        $http = $this->createMock(Http::class);
        $http->expects($this->never())->method('handle');
        $http->expects($this->never())->method('terminate');

        $responder = $this->responder([$this->throwingRequest(), null]);
        $responder->expects($this->once())->method('respond');

        (new RoadRunnerWorker($http, $this->factory()))->run($responder);
    }

    public function testRunSendsErrorResponseWhenKernelThrows(): void
    {
        $captured = null;
        $http     = $this->createMock(Http::class);
        $http->method('handle')->willThrowException(new RuntimeException('boom'));
        $http->expects($this->never())->method('terminate');

        $responder = $this->responder([$this->serverRequest(), false]);
        $responder->expects($this->once())->method('respond');

        (new RoadRunnerWorker($http, $this->factory($captured)))->run($responder);

        $this->assertInstanceOf(Response::class, $captured);
        $this->assertSame(500, $captured->getStatusCode());
        $this->assertSame('boom', $captured->getContent());
    }

    public function testRunContinuesAfterKernelFailureUntilNoRequest(): void
    {
        $http = $this->createMock(Http::class);
        $http->method('handle')->willThrowException(new RuntimeException('boom'));
        $http->expects($this->never())->method('terminate');

        $responder = $this->responder([$this->serverRequest(), null]);
        $responder->expects($this->once())->method('respond');

        (new RoadRunnerWorker($http, $this->factory()))->run($responder);
    }

    public function testRunHandlesNextRequestAfterKernelFailure(): void
    {
        $http = $this->createMock(Http::class);
        $http->method('handle')
            ->willReturnOnConsecutiveCalls(
                $this->throwException(new RuntimeException('boom')),
                new Response('ok', 200)
            );
        $http->expects($this->once())->method('terminate');

        $responder = $this->responder([$this->serverRequest(), $this->serverRequest(), null]);
        $responder->expects($this->exactly(2))->method('respond');

        (new RoadRunnerWorker($http, $this->factory()))->run($responder);
    }

    public function testRunTerminatesWhenRespondingFails(): void
    {
        $calls     = 0;
        $converted = $this->psr7Response();

        $factory = static function (Response $response) use (&$calls, $converted): ResponseInterface {
            ++$calls;

            if ($calls === 1) {
                throw new RuntimeException('respond failed');
            }

            return $converted;
        };

        $http = $this->createMock(Http::class);
        $http->method('handle')->willReturn(new Response('ok', 200));
        $http->expects($this->once())->method('terminate');

        $responder = $this->responder([$this->serverRequest(), null]);
        $responder->expects($this->once())->method('respond');

        (new RoadRunnerWorker($http, $factory))->run($responder);
    }

    public function testRunContinuesAfterFailedConversionUntilStopped(): void
    {
        $http = $this->createMock(Http::class);
        $http->expects($this->never())->method('handle');
        $http->expects($this->never())->method('terminate');

        $responder = $this->responder([$this->throwingRequest(), false]);
        $responder->expects($this->once())->method('respond');

        (new RoadRunnerWorker($http, $this->factory()))->run($responder);
    }

    public function testRunHandlesNextRequestAfterFailedConversion(): void
    {
        $http = $this->createMock(Http::class);
        $http->expects($this->once())->method('handle')->willReturn(new Response('ok', 200));
        $http->expects($this->once())->method('terminate');

        $responder = $this->responder([$this->throwingRequest(), $this->serverRequest(), false]);
        $responder->expects($this->exactly(2))->method('respond');

        (new RoadRunnerWorker($http, $this->factory()))->run($responder);
    }

    #[AllowMockObjectsWithoutExpectations]
    public function testRunPropagatesTerminationFailure(): void
    {
        $http = $this->createMock(Http::class);
        $http->method('handle')->willReturn(new Response('ok', 200));
        $http->method('terminate')->willThrowException(new RuntimeException('terminate failed'));

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('terminate failed');

        (new RoadRunnerWorker($http, $this->factory()))->run($this->responder([$this->serverRequest()]));
    }

    public function testRunTerminatesWhenRespondingFailsAndWorkerStops(): void
    {
        $calls     = 0;
        $converted = $this->psr7Response();

        $factory = static function (Response $response) use (&$calls, $converted): ResponseInterface {
            ++$calls;

            if ($calls === 1) {
                throw new RuntimeException('respond failed');
            }

            return $converted;
        };

        $http = $this->createMock(Http::class);
        $http->method('handle')->willReturn(new Response('ok', 200));
        $http->expects($this->once())->method('terminate');

        $responder = $this->responder([$this->serverRequest(), false]);
        $responder->expects($this->once())->method('respond');

        (new RoadRunnerWorker($http, $factory))->run($responder);
    }

    public function testRunHandlesMultipleRequestsThenStops(): void
    {
        $http = $this->createMock(Http::class);
        $http->expects($this->exactly(2))->method('handle')->willReturn(new Response('ok', 200));
        $http->expects($this->exactly(2))->method('terminate');

        $responder = $this->responder([$this->serverRequest(), $this->serverRequest(), false]);
        $responder->expects($this->exactly(2))->method('respond');

        (new RoadRunnerWorker($http, $this->factory()))->run($responder);
    }

    #[AllowMockObjectsWithoutExpectations]
    public function testRunUsesNativeFactoriesWhenNoCustomResponseFactory(): void
    {
        $stream = $this->createStub(StreamInterface::class);

        $streamFactory = $this->createStub(StreamFactoryInterface::class);
        $streamFactory->method('createStream')->willReturn($stream);

        $psr7 = $this->psr7Response();

        $responseFactory = $this->createStub(ResponseFactoryInterface::class);
        $responseFactory->method('createResponse')->willReturn($psr7);

        $http = $this->createMock(Http::class);
        $http->method('handle')->willReturn(new Response('native', 200));

        $responder = $this->responder([$this->serverRequest(), false]);
        $responder->expects($this->once())->method('respond')->with($psr7);

        (new RoadRunnerWorker($http, null, $responseFactory, $streamFactory))->run($responder);
    }

    public function testToPsr7RejectsMissingNativeFactories(): void
    {
        $reflection = new ReflectionClass(RoadRunnerWorker::class);
        $worker     = $reflection->newInstanceWithoutConstructor();

        foreach (['responseFactory', 'psr7Factory', 'streamFactory'] as $property) {
            $reflection->getProperty($property)->setValue($worker, null);
        }

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('The native responder requires both psr7Factory and streamFactory');

        $reflection->getMethod('toPsr7')->invoke($worker, new Response('x', 200));
    }

    /**
     * @param array<int, ServerRequestInterface|false|null> $requests
     * @return RoadRunnerResponderInterface&MockObject
     */
    private function responder(array $requests): RoadRunnerResponderInterface
    {
        $responder = $this->createMock(RoadRunnerResponderInterface::class);
        $responder->method('waitRequest')->willReturnOnConsecutiveCalls(...$requests);

        return $responder;
    }

    /**
     * @param Response|null $captured
     * @return \Closure(Response): ResponseInterface
     */
    private function factory(?Response &$captured = null): \Closure
    {
        $response = $this->psr7Response();

        return static function (Response $value) use (&$captured, $response): ResponseInterface {
            $captured = $value;

            return $response;
        };
    }

    private function throwingRequest(): ServerRequestInterface
    {
        $request = $this->createStub(ServerRequestInterface::class);
        $request->method('getHeaders')->willReturn([]);
        $request->method('getUri')->willThrowException(new RuntimeException('bad request'));

        return $request;
    }

    private function serverRequest(): ServerRequestInterface
    {
        $uri = $this->createStub(UriInterface::class);
        $uri->method('__toString')->willReturn('/worker');
        $uri->method('getScheme')->willReturn('http');

        $body = $this->createStub(StreamInterface::class);
        $body->method('__toString')->willReturn('');

        $request = $this->createStub(ServerRequestInterface::class);
        $request->method('getHeaders')->willReturn([]);
        $request->method('getUri')->willReturn($uri);
        $request->method('getMethod')->willReturn('GET');
        $request->method('getServerParams')->willReturn([]);
        $request->method('getHeaderLine')->willReturn('');
        $request->method('getQueryParams')->willReturn([]);
        $request->method('getParsedBody')->willReturn(null);
        $request->method('getCookieParams')->willReturn([]);
        $request->method('getUploadedFiles')->willReturn([]);
        $request->method('getBody')->willReturn($body);

        return $request;
    }

    private function psr7Response(): ResponseInterface
    {
        $response = $this->createStub(ResponseInterface::class);
        $response->method('withProtocolVersion')->willReturnSelf();
        $response->method('withBody')->willReturnSelf();
        $response->method('withHeader')->willReturnSelf();

        return $response;
    }
}
