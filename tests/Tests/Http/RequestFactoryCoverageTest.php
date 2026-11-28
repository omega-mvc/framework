<?php

declare(strict_types=1);

namespace Tests\Http;

use Omega\Http\RequestFactory;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\PreserveGlobalState;
use PHPUnit\Framework\Attributes\RunInSeparateProcess;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamInterface;
use Psr\Http\Message\UploadedFileInterface;
use Psr\Http\Message\UriInterface;
use Tests\TestCase;

use function base64_encode;

#[CoversClass(RequestFactory::class)]
final class RequestFactoryCoverageTest extends TestCase
{
    /** @var array<mixed> */
    private array $originalServer;

    /** @var array<mixed, mixed> */
    private array $originalGet;

    /** @var array<mixed, mixed> */
    private array $originalPost;

    /** @var array<mixed, mixed> */
    private array $originalCookie;

    /** @var array<mixed, mixed> */
    private array $originalFiles;

    protected function setUp(): void
    {
        parent::setUp();

        $this->originalServer = $_SERVER;
        $this->originalGet    = $_GET;
        $this->originalPost   = $_POST;
        $this->originalCookie = $_COOKIE;
        $this->originalFiles  = $_FILES;
    }

    protected function tearDown(): void
    {
        $_SERVER = $this->originalServer;
        $_GET    = $this->originalGet;
        $_POST   = $this->originalPost;
        $_COOKIE = $this->originalCookie;
        $_FILES  = $this->originalFiles;

        parent::tearDown();
    }

    public function testCaptureBuildsRequestFromGlobals(): void
    {
        $_SERVER = [
            'REQUEST_URI'               => '/from-global?a=1',
            'REQUEST_METHOD'            => 'POST',
            'HTTP_X_HTTP_METHOD_OVERRIDE' => 'PATCH',
            'HTTP_X_CUSTOM'             => 'custom',
            'CONTENT_TYPE'              => 'text/plain',
            'WEIRD'                     => ['skip'],
            'HTTP_AUTHORIZATION'        => 'Bearer token',
            'REMOTE_ADDR'               => '[::1]',
        ];
        $_GET    = ['q' => 'value', 'skip' => ['array'], 3 => 'skip-key'];
        $_POST   = ['p' => 'value'];
        $_COOKIE = ['c' => 'value'];
        $_FILES  = [
            'single' => [
                'name'     => 'a.txt',
                'type'     => 'text/plain',
                'tmp_name' => '/tmp/a',
                'error'    => 0,
                'size'     => 1,
            ],
            'multi' => [
                'name'     => ['b.txt', 9],
                'type'     => ['text/plain'],
                'tmp_name' => ['/tmp/b'],
                'error'    => [0],
                'size'     => [2],
            ],
            'bad' => 'not an array',
            7     => ['name' => 'skip numeric key'],
            'invalid' => [
                'name'     => 123,
                'type'     => null,
                'tmp_name' => false,
                'error'    => 'wrong',
                'size'     => 'wrong',
            ],
        ];

        $request = RequestFactory::capture();

        $this->assertSame('/from-global?a=1', $request->getUrl());
        $this->assertSame('PATCH', $request->getMethod());
        $this->assertSame('::1', $request->getRemoteAddress());
        $this->assertSame('value', $request->getQuery('q'));
        $this->assertSame('value', $request->getPost('p'));
        $this->assertSame('value', $request->getCookie('c'));
        $this->assertSame('Bearer token', $request->getHeaders('authorization'));
        $this->assertSame('custom', $request->getHeaders('x-custom'));
        $this->assertSame('text/plain', $request->getHeaders('content-type'));
        $this->assertSame('a.txt', $request->getFile('single')['name']);
        $this->assertSame(['b.txt'], $request->getFile('multi')['name']);
        $this->assertSame([0], $request->getFile('multi')['error']);
        $this->assertSame('', $request->getFile('invalid')['name']);
        $this->assertSame('', $request->getFile('invalid')['type']);
        $this->assertSame('', $request->getFile('invalid')['tmp_name']);
        $this->assertSame(0, $request->getFile('invalid')['error']);
        $this->assertSame(0, $request->getFile('invalid')['size']);
    }

    public function testGetHeadersUsesBasicAuthorizationFallback(): void
    {
        $_SERVER = [
            'REQUEST_URI'    => '/',
            'REQUEST_METHOD' => 'GET',
            'PHP_AUTH_USER'  => 'user',
            'PHP_AUTH_PW'    => 'pass',
        ];

        $request = RequestFactory::capture();

        $this->assertSame('Basic ' . base64_encode('user:pass'), $request->getHeaders('authorization'));
    }

    public function testGetHeadersUsesDigestAuthorizationFallback(): void
    {
        $_SERVER = [
            'REQUEST_URI'     => '/',
            'REQUEST_METHOD'  => 'GET',
            'PHP_AUTH_DIGEST' => 'Digest abc',
        ];

        $request = RequestFactory::capture();

        $this->assertSame('Digest abc', $request->getHeaders('authorization'));
    }

    public function testGetHeadersKeepsExplicitAuthorizationHeader(): void
    {
        $_SERVER = [
            'REQUEST_URI'    => '/',
            'REQUEST_METHOD' => 'GET',
            'HTTP_Authorization' => 'Bearer explicit',
        ];

        $request = RequestFactory::capture();

        $this->assertSame('Bearer explicit', $request->getHeaders('authorization'));
    }

    public function testMethodFallbacksAndOverrides(): void
    {
        $_SERVER = ['REQUEST_URI' => '/', 'REQUEST_METHOD' => ['invalid']];
        $this->assertSame('GET', RequestFactory::capture()->getMethod());

        $_SERVER = [
            'REQUEST_URI'                 => '/',
            'REQUEST_METHOD'              => 'POST',
            'HTTP_X_HTTP_METHOD_OVERRIDE' => 'lowercase',
        ];
        $this->assertSame('POST', RequestFactory::capture()->getMethod());

        $_SERVER = [
            'REQUEST_URI'                 => '/',
            'REQUEST_METHOD'              => 'GET',
            'HTTP_X_HTTP_METHOD_OVERRIDE' => ['invalid'],
        ];
        $this->assertSame('GET', RequestFactory::capture()->getMethod());

        $_SERVER = ['REQUEST_URI' => '/', 'REQUEST_METHOD' => 'DELETE'];
        $this->assertSame('DELETE', RequestFactory::capture()->getMethod());
    }

    public function testClientFallbacks(): void
    {
        $_SERVER = ['REQUEST_URI' => '/'];
        $this->assertSame('::1', RequestFactory::capture()->getRemoteAddress());

        $_SERVER = ['REQUEST_URI' => '/', 'REMOTE_ADDR' => ''];
        // @phpstan-ignore method.alreadyNarrowedType
        $this->assertSame('::1', RequestFactory::capture()->getRemoteAddress());

        $_SERVER = ['REQUEST_URI' => '/', 'REMOTE_ADDR' => ['invalid']];
        // @phpstan-ignore method.alreadyNarrowedType
        $this->assertSame('::1', RequestFactory::capture()->getRemoteAddress());

        $_SERVER = ['REQUEST_URI' => '/', 'REMOTE_ADDR' => '192.168.1.10'];
        $this->assertSame('192.168.1.10', RequestFactory::capture()->getRemoteAddress());
    }

    public function testFromPsr7ServerRequestMapsAllFields(): void
    {
        $uri = $this->createStub(UriInterface::class);
        $uri->method('__toString')->willReturn('https://example.com/path?q=1');
        $uri->method('getScheme')->willReturn('https');

        $stream = $this->createStub(StreamInterface::class);
        $stream->method('__toString')->willReturn('{"key":"value"}');

        $uploaded = $this->uploadedFile('/tmp/uploaded.txt', 'a.txt', 'text/plain', 0, 10);

        $psr7 = $this->createStub(ServerRequestInterface::class);
        $psr7->method('getHeaders')->willReturn([
            'X-Test'       => ['one', 'two'],
            'Content-Type' => ['application/json'],
        ]);
        $psr7->method('getUri')->willReturn($uri);
        $psr7->method('getMethod')->willReturn('POST');
        $psr7->method('getServerParams')->willReturn(['REMOTE_ADDR' => '1.2.3.4']);
        $psr7->method('getHeaderLine')->willReturn('PUT');
        $psr7->method('getQueryParams')->willReturn(['q' => 'v', 'skip' => ['array'], 3 => 'skip']);
        $psr7->method('getParsedBody')->willReturn(['p' => 'v']);
        $psr7->method('getCookieParams')->willReturn(['c' => 'v']);
        $psr7->method('getUploadedFiles')->willReturn([
            'single'   => $uploaded,
            'multiple' => [$uploaded, 'skip'],
            'ignored'  => 'skip',
            7          => $uploaded,
        ]);
        $psr7->method('getBody')->willReturn($stream);

        $request = RequestFactory::fromPsr7ServerRequest($psr7);

        $this->assertSame('https://example.com/path?q=1', $request->getUrl());
        $this->assertSame('PUT', $request->getMethod());
        $this->assertSame('one, two', $request->getHeaders('x-test'));
        $this->assertSame('application/json', $request->getHeaders('content-type'));
        $this->assertSame('v', $request->getQuery('q'));
        $this->assertSame('v', $request->getPost('p'));
        $this->assertSame('v', $request->getCookie('c'));
        $this->assertSame('1.2.3.4', $request->getRemoteAddress());
        $this->assertTrue($request->isSecured());
        $this->assertSame('{"key":"value"}', $request->getRawBody());
        $this->assertSame('a.txt', $request->getFile('single')['name']);
        $this->assertSame(['a.txt'], $request->getFile('multiple')['name']);
    }

    public function testFromPsr7ServerRequestWithFallbackValues(): void
    {
        $uri = $this->createStub(UriInterface::class);
        $uri->method('__toString')->willReturn('/local');
        $uri->method('getScheme')->willReturn('');

        $stream = $this->createStub(StreamInterface::class);
        $stream->method('__toString')->willReturn('');

        $psr7 = $this->createStub(ServerRequestInterface::class);
        $psr7->method('getHeaders')->willReturn([]);
        $psr7->method('getUri')->willReturn($uri);
        $psr7->method('getMethod')->willReturn('');
        $psr7->method('getServerParams')->willReturn(['REMOTE_ADDR' => ['invalid']]);
        $psr7->method('getHeaderLine')->willReturn('invalid-override');
        $psr7->method('getQueryParams')->willReturn([]);
        $psr7->method('getParsedBody')->willReturn('not an array');
        $psr7->method('getCookieParams')->willReturn([]);
        $psr7->method('getUploadedFiles')->willReturn([]);
        $psr7->method('getBody')->willReturn($stream);

        $request = RequestFactory::fromPsr7ServerRequest($psr7);

        $this->assertSame('/local', $request->getUrl());
        $this->assertSame('GET', $request->getMethod());
        $this->assertSame([], $request->getPost());
        $this->assertSame('::1', $request->getRemoteAddress());
        $this->assertSame([], $request->getFile());
    }

    public function testFromPsr7ServerRequestNormalizesEmptyUploadMetadata(): void
    {
        $uri = $this->createStub(UriInterface::class);
        $uri->method('__toString')->willReturn('/local');
        $uri->method('getScheme')->willReturn('http');

        $stream = $this->createStub(StreamInterface::class);
        $stream->method('__toString')->willReturn('');

        $emptyUpload = $this->uploadedFile(123, null, null, 4, null);

        $psr7 = $this->createStub(ServerRequestInterface::class);
        $psr7->method('getHeaders')->willReturn([]);
        $psr7->method('getUri')->willReturn($uri);
        $psr7->method('getMethod')->willReturn('GET');
        $psr7->method('getServerParams')->willReturn([]);
        $psr7->method('getHeaderLine')->willReturn('');
        $psr7->method('getQueryParams')->willReturn([]);
        $psr7->method('getParsedBody')->willReturn(null);
        $psr7->method('getCookieParams')->willReturn([]);
        $psr7->method('getUploadedFiles')->willReturn(['empty' => $emptyUpload]);
        $psr7->method('getBody')->willReturn($stream);

        $request = RequestFactory::fromPsr7ServerRequest($psr7);

        $this->assertSame('', $request->getFile('empty')['name']);
        $this->assertSame('', $request->getFile('empty')['type']);
        $this->assertSame('', $request->getFile('empty')['tmp_name']);
        $this->assertSame(4, $request->getFile('empty')['error']);
        $this->assertSame(0, $request->getFile('empty')['size']);
    }

    #[RunInSeparateProcess]
    #[PreserveGlobalState(false)]
    public function testGetHeadersUsesApacheRequestHeadersWhenAvailable(): void
    {
        require_once __DIR__ . '/Support/apache_request_headers.php';

        $_SERVER = ['REQUEST_URI' => '/', 'REQUEST_METHOD' => 'GET'];

        $request = RequestFactory::capture();

        $this->assertSame('yes', $request->getHeaders('x-apache'));
        $this->assertNull($request->getHeaders('x-bad'));
    }

    /**
     * @return \PHPUnit\Framework\MockObject\Stub&UploadedFileInterface
     */
    private function uploadedFile(
        int|string $uri,
        ?string $name,
        ?string $type,
        int $error,
        ?int $size
    ): UploadedFileInterface {
        $stream = $this->createStub(StreamInterface::class);
        $stream->method('getMetadata')->willReturn($uri);

        $uploaded = $this->createStub(UploadedFileInterface::class);
        $uploaded->method('getStream')->willReturn($stream);
        $uploaded->method('getClientFilename')->willReturn($name);
        $uploaded->method('getClientMediaType')->willReturn($type);
        $uploaded->method('getError')->willReturn($error);
        $uploaded->method('getSize')->willReturn($size);

        return $uploaded;
    }
}
