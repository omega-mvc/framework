<?php

declare(strict_types=1);

namespace Tests\Http;

use Exception;
use Omega\Http\Request;
use PHPUnit\Framework\Attributes\CoversClass;
use Tests\TestCase;

#[CoversClass(Request::class)]
final class RequestCoverageTest extends TestCase
{
    public function testDuplicateOverridesEverySection(): void
    {
        $original = new Request(
            'http://localhost/',
            ['q' => 'old'],
            ['p' => 'old'],
            ['a' => 'old'],
            ['c' => 'old'],
            [],
            ['h' => 'old']
        );

        $files = [
            'upload' => [
                'name'     => 'a.txt',
                'type'     => 'text/plain',
                'tmp_name' => '/tmp/a.txt',
                'error'    => 0,
                'size'     => 1,
            ],
        ];

        $duplicate = $original->duplicate(
            ['q' => 'new'],
            ['p' => 'new'],
            ['a' => 'new'],
            ['c' => 'new'],
            $files,
            ['h' => 'new']
        );

        $this->assertSame('old', $original->getQuery('q'));
        $this->assertSame('new', $duplicate->getQuery('q'));
        $this->assertSame('old', $original->getPost('p'));
        $this->assertSame('new', $duplicate->getPost('p'));
        $this->assertSame('old', $original->getAttribute('a', 'missing'));
        $this->assertSame('new', $duplicate->getAttribute('a', 'missing'));
        $this->assertSame('old', $original->getCookie('c'));
        $this->assertSame('new', $duplicate->getCookie('c'));
        $this->assertSame([], $original->getFile());
        $this->assertSame($files, $duplicate->getFile());
        $this->assertSame('old', $original->getHeaders('h'));
        $this->assertSame('new', $duplicate->getHeaders('h'));
    }

    public function testDuplicateCoversEveryOverrideCombination(): void
    {
        $base = new Request(
            'http://localhost/',
            ['q' => 'old-q'],
            ['p' => 'old-p'],
            ['a' => 'old-a'],
            ['c' => 'old-c'],
            [],
            ['h' => 'old-h']
        );

        $fileOverrides = [
            'upload' => [
                'name'     => 'a.txt',
                'type'     => 'text/plain',
                'tmp_name' => '/tmp/a.txt',
                'error'    => 0,
                'size'     => 1,
            ],
        ];

        for ($mask = 0; $mask < 64; ++$mask) {
            $query      = ($mask & 1) !== 0 ? ['q' => 'new-q'] : null;
            $post       = ($mask & 2) !== 0 ? ['p' => 'new-p'] : null;
            $attributes = ($mask & 4) !== 0 ? ['a' => 'new-a'] : null;
            $cookies    = ($mask & 8) !== 0 ? ['c' => 'new-c'] : null;
            $files      = ($mask & 16) !== 0 ? $fileOverrides : null;
            $headers    = ($mask & 32) !== 0 ? ['h' => 'new-h'] : null;

            $duplicate = $base->duplicate($query, $post, $attributes, $cookies, $files, $headers);

            $this->assertSame($query === null ? 'old-q' : 'new-q', $duplicate->getQuery('q'));
            $this->assertSame($post === null ? 'old-p' : 'new-p', $duplicate->getPost('p'));
            $this->assertSame(
                $attributes === null ? 'old-a' : 'new-a',
                $duplicate->getAttribute('a', 'missing')
            );
            $this->assertSame($cookies === null ? 'old-c' : 'new-c', $duplicate->getCookie('c'));
            $this->assertSame($files === null ? [] : $fileOverrides, $duplicate->getFile());
            $this->assertSame($headers === null ? 'old-h' : 'new-h', $duplicate->getHeaders('h'));
        }
    }

    public function testGetQueryAndPostWithoutKeyReturnAllParameters(): void
    {
        $request = new Request('http://localhost/', ['q' => 'value'], ['p' => 'value']);

        $this->assertSame(['q' => 'value'], $request->getQuery());
        $this->assertSame(['p' => 'value'], $request->getPost());
    }

    public function testGetCookiesReturnsAllCookies(): void
    {
        $request = new Request('http://localhost/', [], [], [], ['session' => 'abc']);

        $this->assertSame(['session' => 'abc'], $request->getCookies());
    }

    public function testIsMethodMatchesCaseInsensitively(): void
    {
        $request = new Request('http://localhost/', method: 'post');

        $this->assertTrue($request->isMethod('POST'));
        $this->assertFalse($request->isMethod('GET'));
    }

    public function testGetHeadersWithoutKeyReturnsAllHeaders(): void
    {
        $request = new Request('http://localhost/', headers: ['X-Test' => 'value']);

        $this->assertSame(['X-Test' => 'value'], $request->getHeaders());
    }

    public function testIsHeaderReturnsFalseForMissingHeader(): void
    {
        $request = new Request('http://localhost/', headers: ['X-Test' => 'value']);

        $this->assertTrue($request->isHeader('X-Test', 'value'));
        $this->assertFalse($request->isHeader('missing', 'value'));
        $this->assertFalse($request->isHeader('X-Test', 'other'));
    }

    public function testIsSecuredFromSchemeAttribute(): void
    {
        $secured = new Request('http://localhost/', attributes: ['scheme' => 'https']);
        $plain   = new Request('http://localhost/', attributes: ['scheme' => 'http']);

        $this->assertTrue($secured->isSecured());
        $this->assertFalse($plain->isSecured());
    }

    public function testIsSecuredFromServerHttpsVariable(): void
    {
        $original = $_SERVER['HTTPS'] ?? null;

        try {
            $_SERVER['HTTPS'] = 'on';
            $request          = new Request('http://localhost/');

            $this->assertTrue($request->isSecured());

            $_SERVER['HTTPS'] = 'off';

            $this->assertFalse($request->isSecured());
        } finally {
            if ($original === null) {
                unset($_SERVER['HTTPS']);
            } else {
                $_SERVER['HTTPS'] = $original;
            }
        }
    }

    public function testGetJsonBodyThrowsWhenDecodedValueIsNotArray(): void
    {
        $request = new Request('http://localhost/', rawBody: '"plain string"');

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('JSON content was expected to decode to an array, "string" returned.');

        $request->getJsonBody();
    }

    public function testWithMergesAttributes(): void
    {
        $request = new Request('http://localhost/', attributes: ['first' => 'one']);

        $this->assertSame($request, $request->with(['second' => 2]));
        $this->assertSame('one', $request->getAttribute('first', 'missing'));
        $this->assertSame(2, $request->getAttribute('second', 'missing'));
    }

    public function testWrapReturnsAllDataAsSingleElement(): void
    {
        $request = new Request('http://localhost/', ['q' => 'value']);

        $this->assertSame([$request->all()], $request->wrap());
    }

    public function testGetBearerTokenReturnsNullWithoutAuthorization(): void
    {
        $request = new Request('http://localhost/');

        $this->assertNull($request->getBearerToken());
    }

    public function testGetBearerTokenReturnsNullForNonBearerAuthorization(): void
    {
        $request = new Request('http://localhost/', headers: ['Authorization' => 'Basic abc']);

        $this->assertNull($request->getBearerToken());
    }

    public function testInputWithKeyReturnsValueOrDefault(): void
    {
        $request = new Request('http://localhost/', ['q' => 'value']);

        $this->assertSame('value', $request->input('q'));
        $this->assertSame('fallback', $request->input('missing', 'fallback'));
        $this->assertSame('', $request->input('missing'));
    }

    public function testOffsetSetAndOffsetUnsetModifyInput(): void
    {
        $request = new Request('http://localhost/', ['q' => 'old']);

        $request['q'] = 'new';

        $this->assertSame('new', $request['q']);

        unset($request['q']);

        $this->assertNull($request['q']);
    }
}
