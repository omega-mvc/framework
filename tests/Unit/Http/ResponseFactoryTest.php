<?php

declare(strict_types=1);

namespace Tests\Http;

use Omega\Http\Response;
use Omega\Http\ResponseFactory;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Http\Message\StreamInterface;

use function array_merge;
use function file_get_contents;
use function implode;
use function stream_get_contents;
use function strlen;
use function strtolower;

covers(ResponseFactory::class);

beforeEach(function (): void {
    $this->responseFactory = responseFactoryTestFakeResponseFactory();
    $this->streamFactory   = responseFactoryTestFakeStreamFactory();
});

it('converts basic response', function (): void {
    $response = new Response('Hello', 200, ['Content-Type' => 'text/plain']);

    $psr7 = ResponseFactory::toPsr7($response, $this->responseFactory, $this->streamFactory);

    expect($psr7->getStatusCode())->toBe(200);
    expect($psr7->getReasonPhrase())->toBe('OK');
    expect($psr7->getProtocolVersion())->toBe('1.1');
    expect((string) $psr7->getBody())->toBe('Hello');
    expect($psr7->getHeader('Content-Type'))->toBe(['text/plain']);
});

it('converts status code with reason phrase', function (): void {
    $response = new Response('', 404);

    $psr7 = ResponseFactory::toPsr7($response, $this->responseFactory, $this->streamFactory);

    expect($psr7->getStatusCode())->toBe(404);
    expect($psr7->getReasonPhrase())->toBe('Not Found');
});

it('converts array content to json', function (): void {
    $response = new Response(['foo' => 'bar', 'num' => 42]);

    $psr7 = ResponseFactory::toPsr7($response, $this->responseFactory, $this->streamFactory);

    expect((string) $psr7->getBody())->toBe('{"foo":"bar","num":42}');
});

it('converts protocol version', function (): void {
    $response = (new Response())->setProtocolVersion('2.0');

    $psr7 = ResponseFactory::toPsr7($response, $this->responseFactory, $this->streamFactory);

    expect($psr7->getProtocolVersion())->toBe('2.0');
});

it('converts headers', function (): void {
    $response = new Response('x', 200, [
        'Content-Type'  => 'application/json',
        'X-Custom-Name' => 'omega',
    ]);

    $psr7 = ResponseFactory::toPsr7($response, $this->responseFactory, $this->streamFactory);

    expect($psr7->getHeader('Content-Type'))->toBe(['application/json']);
    expect($psr7->getHeader('X-Custom-Name'))->toBe(['omega']);
    expect($psr7->hasHeader('X-Custom-Name'))->toBeTrue();
});

function responseFactoryTestFakeResponseFactory(): ResponseFactoryInterface
{
    return new class () implements ResponseFactoryInterface {
        public function createResponse(int $code = 200, string $reasonPhrase = ''): ResponseInterface
        {
            return new class ($code, $reasonPhrase) implements ResponseInterface {
                /** @var array<string, array<int, string>> */
                private array $headers = [];
                private string $body = '';
                private string $protocolVersion = '1.1';

                public function __construct(private int $code, private string $reasonPhrase)
                {
                }

                public function getStatusCode(): int
                {
                    return $this->code;
                }

                public function withStatus(int $code, string $reasonPhrase = ''): ResponseInterface
                {
                    $clone = clone $this;
                    $clone->code = $code;
                    $clone->reasonPhrase = $reasonPhrase;

                    return $clone;
                }

                public function getReasonPhrase(): string
                {
                    return $this->reasonPhrase;
                }

                public function getProtocolVersion(): string
                {
                    return $this->protocolVersion;
                }

                public function withProtocolVersion(string $version): ResponseInterface
                {
                    $clone = clone $this;
                    $clone->protocolVersion = $version;

                    return $clone;
                }

                /**
                 * @return array<string, array<int, string>>
                 */
                public function getHeaders(): array
                {
                    return $this->headers;
                }

                public function hasHeader(string $name): bool
                {
                    return isset($this->headers[strtolower($name)]);
                }

                /**
                 * @return array<int, string>
                 */
                public function getHeader(string $name): array
                {
                    $name = strtolower($name);

                    return $this->headers[$name] ?? [];
                }

                public function getHeaderLine(string $name): string
                {
                    return implode(', ', $this->getHeader($name));
                }

                /**
                 * @param string|array<int, string> $value
                 */
                public function withHeader(string $name, $value): ResponseInterface
                {
                    $clone = clone $this;
                    $clone->headers[strtolower($name)] = (array) $value;

                    return $clone;
                }

                /**
                 * @param string|array<int, string> $value
                 */
                public function withAddedHeader(string $name, $value): ResponseInterface
                {
                    $clone = clone $this;
                    $name  = strtolower($name);
                    $clone->headers[$name] = array_merge($clone->headers[$name] ?? [], (array) $value);

                    return $clone;
                }

                public function withoutHeader(string $name): ResponseInterface
                {
                    $clone = clone $this;
                    unset($clone->headers[strtolower($name)]);

                    return $clone;
                }

                public function getBody(): StreamInterface
                {
                    return new class ($this->body) implements StreamInterface {
                        public function __construct(private string $content)
                        {
                        }

                        public function __toString(): string
                        {
                            return $this->content;
                        }

                        public function close(): void
                        {
                        }

                        public function detach()
                        {
                            return null;
                        }

                        public function getSize(): int
                        {
                            return strlen($this->content);
                        }

                        public function tell(): int
                        {
                            return 0;
                        }

                        public function eof(): bool
                        {
                            return true;
                        }

                        public function isSeekable(): bool
                        {
                            return false;
                        }

                        public function seek(int $offset, int $whence = SEEK_SET): void
                        {
                        }

                        public function rewind(): void
                        {
                        }

                        public function isWritable(): bool
                        {
                            return true;
                        }

                        public function write(string $string): int
                        {
                            $this->content = $string;

                            return strlen($string);
                        }

                        public function isReadable(): bool
                        {
                            return true;
                        }

                        public function read(int $length): string
                        {
                            return $this->content;
                        }

                        public function getContents(): string
                        {
                            return $this->content;
                        }

                        public function getMetadata(?string $key = null)
                        {
                            return $key === null ? [] : null;
                        }
                    };
                }

                public function withBody(StreamInterface $body): ResponseInterface
                {
                    $clone = clone $this;
                    $clone->body = (string) $body;

                    return $clone;
                }
            };
        }
    };
}

function responseFactoryTestFakeStreamFactory(): StreamFactoryInterface
{
    return new class () implements StreamFactoryInterface {
        public function createStream(string $content = ''): StreamInterface
        {
            return new class ($content) implements StreamInterface {
                public function __construct(private string $content)
                {
                }

                public function __toString(): string
                {
                    return $this->content;
                }

                public function close(): void
                {
                }

                public function detach()
                {
                    return null;
                }

                public function getSize(): int
                {
                    return strlen($this->content);
                }

                public function tell(): int
                {
                    return 0;
                }

                public function eof(): bool
                {
                    return true;
                }

                public function isSeekable(): bool
                {
                    return false;
                }

                public function seek(int $offset, int $whence = SEEK_SET): void
                {
                }

                public function rewind(): void
                {
                }

                public function isWritable(): bool
                {
                    return true;
                }

                public function write(string $string): int
                {
                    $this->content = $string;

                    return strlen($string);
                }

                public function isReadable(): bool
                {
                    return true;
                }

                public function read(int $length): string
                {
                    return $this->content;
                }

                public function getContents(): string
                {
                    return $this->content;
                }

                public function getMetadata(?string $key = null)
                {
                    return $key === null ? [] : null;
                }
            };
        }

        public function createStreamFromFile(string $filename, string $mode = 'r'): StreamInterface
        {
            return $this->createStream((string) file_get_contents($filename));
        }

        public function createStreamFromResource($resource): StreamInterface
        {
            return $this->createStream((string) stream_get_contents($resource));
        }
    };
}