<?php

declare(strict_types=1);

namespace Tests\Http;

use Omega\Http\Request;
use Omega\Http\RequestFactory;
use PHPUnit\Framework\Attributes\CoversClass;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamInterface;
use Psr\Http\Message\UploadedFileInterface;
use Psr\Http\Message\UriInterface;
use ReflectionMethod;
use Tests\TestCase;

use function array_keys;

#[CoversClass(RequestFactory::class)]
final class RequestFactoryPathsCoverageTest extends TestCase
{
    /** @var array<mixed, mixed> */
    private array $originalServer;

    protected function setUp(): void
    {
        parent::setUp();

        $this->originalServer = $_SERVER;
    }

    protected function tearDown(): void
    {
        $_SERVER = $this->originalServer;

        parent::tearDown();
    }

    public function testNormalizeFileEntryCoversEveryFieldShapeCombination(): void
    {
        $method  = $this->privateMethod('normalizeFileEntry');
        $factory = new RequestFactory();
        $strings = ['value', ['value', 3], 123];
        $numbers = [7, [7, 'x'], 'x'];
        $combos  = 0;

        foreach ($strings as $name) {
            foreach ($strings as $type) {
                foreach ($strings as $tmpName) {
                    foreach ($numbers as $error) {
                        foreach ($numbers as $size) {
                            $result = $method->invoke($factory, [
                                'name'     => $name,
                                'type'     => $type,
                                'tmp_name' => $tmpName,
                                'error'    => $error,
                                'size'     => $size,
                            ]);

                            $this->assertIsArray($result);
                            $this->assertSame(
                                ['name', 'type', 'tmp_name', 'error', 'size'],
                                array_keys($result)
                            );
                            $combos++;
                        }
                    }
                }
            }
        }

        $this->assertSame(243, $combos);
    }

    public function testNormalizeToStringArrayCoversNullEmptyAndFilteredEntries(): void
    {
        $method  = $this->privateMethod('normalizeToStringArray');
        $factory = new RequestFactory();

        $this->assertSame([], $method->invoke($factory, null));
        $this->assertSame([], $method->invoke($factory, []));
        $this->assertSame(
            ['ok' => '1', 'zero' => '0'],
            $method->invoke($factory, [
                'ok'   => 1,
                'zero' => 0,
                3      => 'skip key',
                'skip' => ['array'],
                'null' => null,
            ])
        );
    }

    public function testNormalizeGlobalFilesCoversEveryEntryShape(): void
    {
        $method  = $this->privateMethod('normalizeGlobalFiles');
        $factory = new RequestFactory();
        $cases   = [
            null,
            [],
            ['ok' => ['name' => 'a.txt']],
            [3 => ['name' => 'a.txt']],
            ['bad' => 'not an array'],
            [3 => 'not an array'],
            ['ok' => [], 3 => [], 'bad' => 'x', 4 => 'x'],
        ];

        foreach ($cases as $case) {
            $result = $method->invoke($factory, $case);
            $this->assertIsArray($result);
        }
    }

    public function testNormalizeFilesCoversEveryValueShape(): void
    {
        $method     = $this->privateMethod('normalizeFiles');
        $factory    = new RequestFactory();
        $withUri    = $this->uploadedFile('/tmp/a.txt');
        $withoutUri = $this->uploadedFile(123);
        $cases      = [
            [],
            ['skip' => 'x'],
            [7 => $withUri],
            ['one' => $withUri],
            ['no-uri' => $withoutUri],
            ['list' => [$withUri, 'skip', 7 => $withoutUri]],
            ['mixed' => ['skip', 7 => $withUri, 'list' => [$withoutUri]]],
        ];

        foreach ($cases as $case) {
            $result = $method->invoke($factory, $case);
            $this->assertIsArray($result);
        }
    }

    public function testNormalizeFileListCoversEveryEntryShape(): void
    {
        $method     = $this->privateMethod('normalizeFileList');
        $factory    = new RequestFactory();
        $withUri    = $this->uploadedFile('/tmp/a.txt');
        $withoutUri = $this->uploadedFile(123);
        $cases      = [
            [],
            ['skip'],
            [$withUri],
            [$withUri, 'skip'],
            [3 => $withoutUri],
            [$withUri, $withoutUri],
            ['skip', 7 => 'skip'],
        ];

        foreach ($cases as $case) {
            $result = $method->invoke($factory, $case);
            $this->assertIsArray($result);
        }
    }

    public function testLowercaseKeysCoversEveryKeyAndValueShape(): void
    {
        $method  = $this->privateMethod('lowercaseKeys');
        $factory = new RequestFactory();
        $cases   = [
            [],
            ['Mixed' => 'value'],
            ['Mixed' => 123],
            [3 => 'value'],
            [3 => 123],
            ['A' => 'b', 3 => 'c', 'D' => []],
        ];

        foreach ($cases as $case) {
            $result = $method->invoke($factory, $case);
            $this->assertIsArray($result);
        }
    }

    public function testGetHeadersCoversEveryServerShapeAndAuthorizationFallback(): void
    {
        $method = $this->privateMethod('getHeaders');
        $shapes = [
            [],
            ['WEIRD' => ['skip']],
            ['HTTP_X_CUSTOM' => 'custom'],
            ['CONTENT_TYPE' => 'text/plain'],
            ['OTHER' => 'value'],
            ['HTTP_X_CUSTOM' => 'custom', 'CONTENT_TYPE' => 'text/plain', 'WEIRD' => ['skip'], 'OTHER' => 'value'],
        ];
        $auth   = [
            [],
            ['Authorization' => 'Bearer exact'],
            ['HTTP_AUTHORIZATION' => 'Bearer header'],
            ['REDIRECT_HTTP_AUTHORIZATION' => 'Bearer redirect'],
            ['REDIRECT_HTTP_AUTHORIZATION' => ['invalid'], 'PHP_AUTH_DIGEST' => 'Digest fallback'],
            ['HTTP_AUTHORIZATION' => ['invalid'], 'PHP_AUTH_USER' => 'user', 'PHP_AUTH_PW' => 'pass'],
            ['PHP_AUTH_USER' => 'user', 'PHP_AUTH_PW' => 'pass'],
            ['PHP_AUTH_USER' => ['invalid'], 'PHP_AUTH_PW' => 'pass'],
            ['PHP_AUTH_USER' => 'user', 'PHP_AUTH_PW' => ['invalid']],
            ['PHP_AUTH_USER' => 'user'],
            ['PHP_AUTH_DIGEST' => 'Digest abc'],
            ['PHP_AUTH_DIGEST' => ['invalid']],
        ];

        foreach ($shapes as $shape) {
            foreach ($auth as $scenario) {
                $_SERVER = $shape + $scenario;

                $result = $method->invoke(new RequestFactory());
                $this->assertIsArray($result);
            }
        }
    }

    public function testFromPsr7ServerRequestCoversEveryPathCombination(): void
    {
        $methods    = ['', 'GET', 'POST', 'PUT'];
        $overrides  = ['PUT', 'bad-override'];
        $bodies     = [['p' => 'v'], 'not an array'];
        $remotes    = [['REMOTE_ADDR' => '1.2.3.4'], ['REMOTE_ADDR' => ['invalid']]];
        $headerSets = [[], ['X-Test' => ['a', 'b']]];
        $files      = [
            'one'  => $this->uploadedFile('/tmp/a.txt'),
            'list' => [$this->uploadedFile('/tmp/b.txt')],
            7      => $this->uploadedFile('/tmp/c.txt'),
            'skip' => 'x',
        ];

        foreach ($methods as $method) {
            foreach ($overrides as $override) {
                foreach ($bodies as $body) {
                    foreach ($remotes as $remote) {
                        foreach ($headerSets as $headers) {
                            $request = RequestFactory::fromPsr7ServerRequest(
                                $this->psr7($method, $override, $body, $remote, $headers, $files)
                            );
                            $this->assertInstanceOf(Request::class, $request);
                        }
                    }
                }
            }
        }
    }

    private function privateMethod(string $name): ReflectionMethod
    {
        return new ReflectionMethod(RequestFactory::class, $name);
    }

    /**
     * @param array<mixed, mixed> $headers
     * @param array<mixed, mixed> $serverParams
     * @param array<mixed, mixed> $files
     */
    private function psr7(
        string $method,
        string $override,
        mixed $body,
        array $serverParams,
        array $headers,
        array $files
    ): ServerRequestInterface {
        $uri = $this->createStub(UriInterface::class);
        $uri->method('__toString')->willReturn('/local');
        $uri->method('getScheme')->willReturn('http');

        $stream = $this->createStub(StreamInterface::class);
        $stream->method('__toString')->willReturn('');

        $psr7 = $this->createStub(ServerRequestInterface::class);
        $psr7->method('getHeaders')->willReturn($headers);
        $psr7->method('getUri')->willReturn($uri);
        $psr7->method('getMethod')->willReturn($method);
        $psr7->method('getServerParams')->willReturn($serverParams);
        $psr7->method('getHeaderLine')->willReturn($override);
        $psr7->method('getQueryParams')->willReturn([]);
        $psr7->method('getParsedBody')->willReturn($body);
        $psr7->method('getCookieParams')->willReturn([]);
        $psr7->method('getUploadedFiles')->willReturn($files);
        $psr7->method('getBody')->willReturn($stream);

        return $psr7;
    }

    /**
     * @return \PHPUnit\Framework\MockObject\Stub&UploadedFileInterface
     */
    private function uploadedFile(int|string $uri): UploadedFileInterface
    {
        $stream = $this->createStub(StreamInterface::class);
        $stream->method('getMetadata')->willReturn($uri);

        $uploaded = $this->createStub(UploadedFileInterface::class);
        $uploaded->method('getStream')->willReturn($stream);
        $uploaded->method('getClientFilename')->willReturn('a.txt');
        $uploaded->method('getClientMediaType')->willReturn('text/plain');
        $uploaded->method('getError')->willReturn(0);
        $uploaded->method('getSize')->willReturn(1);

        return $uploaded;
    }
}
