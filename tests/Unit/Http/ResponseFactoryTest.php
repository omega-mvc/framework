<?php

declare(strict_types=1);

namespace Tests\Http;

use Omega\Http\Response;
use Omega\Http\ResponseFactory;
use PHPUnit\Framework\Attributes\CoversClass;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Http\Message\StreamInterface;
use Tests\TestCase;

use function array_merge;
use function file_get_contents;
use function implode;
use function stream_get_contents;
use function strlen;
use function strtolower;

#[CoversClass(ResponseFactory::class)]
final class ResponseFactoryTest extends TestCase
{
    private ResponseFactoryInterface $responseFactory;

    private StreamFactoryInterface $streamFactory;

    protected function setUp(): void
    {
        parent::setUp();

        $this->responseFactory = $this->fakeResponseFactory();
        $this->streamFactory   = $this->fakeStreamFactory();
    }

    public function testConvertsBasicResponse(): void
    {
        $response = new Response('Hello', 200, ['Content-Type' => 'text/plain']);

        $psr7 = ResponseFactory::toPsr7($response, $this->responseFactory, $this->streamFactory);

        $this->assertSame(200, $psr7->getStatusCode());
        $this->assertSame('OK', $psr7->getReasonPhrase());
        $this->assertSame('1.1', $psr7->getProtocolVersion());
        $this->assertSame('Hello', (string) $psr7->getBody());
        $this->assertSame(['text/plain'], $psr7->getHeader('Content-Type'));
    }

    public function testConvertsStatusCodeWithReasonPhrase(): void
    {
        $response = new Response('', 404);

        $psr7 = ResponseFactory::toPsr7($response, $this->responseFactory, $this->streamFactory);

        $this->assertSame(404, $psr7->getStatusCode());
        $this->assertSame('Not Found', $psr7->getReasonPhrase());
    }

    public function testConvertsArrayContentToJson(): void
    {
        $response = new Response(['foo' => 'bar', 'num' => 42]);

        $psr7 = ResponseFactory::toPsr7($response, $this->responseFactory, $this->streamFactory);

        $this->assertSame('{"foo":"bar","num":42}', (string) $psr7->getBody());
    }

    public function testConvertsProtocolVersion(): void
    {
        $response = (new Response())->setProtocolVersion('2.0');

        $psr7 = ResponseFactory::toPsr7($response, $this->responseFactory, $this->streamFactory);

        $this->assertSame('2.0', $psr7->getProtocolVersion());
    }

    public function testConvertsHeaders(): void
    {
        $response = new Response('x', 200, [
            'Content-Type'  => 'application/json',
            'X-Custom-Name' => 'omega',
        ]);

        $psr7 = ResponseFactory::toPsr7($response, $this->responseFactory, $this->streamFactory);

        $this->assertSame(['application/json'], $psr7->getHeader('Content-Type'));
        $this->assertSame(['omega'], $psr7->getHeader('X-Custom-Name'));
        $this->assertTrue($psr7->hasHeader('X-Custom-Name'));
    }

    private function fakeResponseFactory(): ResponseFactoryInterface
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

    private function fakeStreamFactory(): StreamFactoryInterface
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
}
