<?php

declare(strict_types=1);

namespace Tests\Http;

use Closure;
use Exception;
use Omega\Http\Request;
use Omega\Http\Upload\UploadFile;
use Omega\Validator\Rule\FilterPool;
use Omega\Validator\Rule\ValidPool;
use Omega\Validator\Validator;
use Tests\TestCase;

use function class_exists;
use function is_array;
use function ltrim;
use function Omega\Application\slash;

final class RequestTest extends TestCase
{
    private Request $request;

    private Request $postRequest;

    private Request $putRequest;

    protected function fixturePath(string $path = ''): string
    {
        return slash(__DIR__ . ($path !== '' ? '/' . ltrim($path, '/') : ''));
    }

    protected function setUp(): void
    {
        parent::setUp();

        if (
            !class_exists(Validator::class) ||
            !class_exists(ValidPool::class) ||
            !class_exists(FilterPool::class)
        ) {
            $this->markTestSkipped('Validator package not installed.');
        }

        $this->request = new Request(
            'http://localhost/',
            ['query_1'   => 'query'],
            ['post_1'    => 'post'],
            ['custom'    => 'custom'],
            ['cookies'   => 'cookies'],
            [
                'file_1' => [
                    'name'      => 'file_name',
                    'type'      => 'text',
                    'tmp_name'  => 'tmp_name',
                    'error'     => 0,
                    'size'      => 0,
                ],
            ],
            ['header_1'  => 'header', 'header_2' => '123', 'foo' => 'bar'],
            'GET',
            '127:0:0:1',
            '{"response":"ok"}'
        );

        $this->postRequest = new Request(
            'http://localhost/',
            ['query_1'   => 'query'],
            ['post_1'    => 'post'],
            ['custom'    => 'custom'],
            ['cookies'   => 'cookies'],
            [
                'file_1' => [
                    'name'      => 'file_name',
                    'type'      => 'text',
                    'tmp_name'  => 'tmp_name',
                    'error'     => 0,
                    'size'      => 0,
                ],
                'file_2' => [
                    'name'      => 'test123.txt',
                    'type'      => 'file',
                    'tmp_name'  => $this->fixturePath('/fixtures/application-read/upload/test123.tmp'),
                    'error'     => 0,
                    'size'      => 1,
                ],
            ],
            ['header_1'  => 'header', 'header_2' => '123', 'foo' => 'bar'],
            'POST',
            '127:0:0:1',
            '{"response":"ok"}'
        );

        $this->putRequest = new Request('test.test', [], [], [], [], [], [
            'content-type' => 'app/json',
        ], '', '', '{"response":"ok"}');
    }

    public function testHasSameUrl(): void
    {
        $this->assertEquals('http://localhost/', $this->request->getUrl());
    }

    public function testHasSameQuery(): void
    {
        $this->assertEquals('query', $this->request->getQuery('query_1'));
        $this->assertEquals('query', $this->request->query()->get('query_1'));
    }

    public function testHasSamePost(): void
    {
        $this->assertEquals('post', $this->request->getPost('post_1'));
        $this->assertEquals('post', $this->request->post()->get('post_1'));
    }

    public function testHasSameCookies(): void
    {
        $this->assertEquals('cookies', $this->request->getCookie('cookies'));
    }

    public function testHasSameFile(): void
    {
        $file = $this->request->getFile('file_1');

        $this->assertEquals('file_name', $file['name']);
        $this->assertEquals('text', $file['type']);
        $this->assertEquals('tmp_name', $file['tmp_name']);
        $this->assertEquals(0, $file['error']);
        $this->assertEquals(0, $file['size']);
    }

    public function testHasSameHeader(): void
    {
        $this->assertEquals('header', $this->request->getHeaders('header_1'));
    }

    public function testHasSameMethod(): void
    {
        $this->assertEquals('GET', $this->request->getMethod());
    }

    public function testHasSameIp(): void
    {
        $this->assertEquals('127:0:0:1', $this->request->getRemoteAddress());
    }

    public function testHasSameBody(): void
    {
        $this->assertEquals('{"response":"ok"}', $this->request->getRawBody());
    }

    public function testHasSameBodyJson(): void
    {
        $this->assertEquals(['response' => 'ok'], $this->request->getJsonBody());
    }

    public function testIsNotSecureRequest(): void
    {
        $this->assertFalse($this->request->isSecured());
    }

    public function testHasHeader(): void
    {
        $this->assertTrue($this->request->hasHeader('header_2'));
    }

    public function testIsHeaderContains(): void
    {
        $this->assertTrue($this->request->isHeader('foo', 'bar'));
    }

    public function testCanGetAllProperty(): void
    {
        $this->assertEquals([
            'header_1'          => 'header',
            'header_2'          => 123,
            'foo'               => 'bar',
            'query_1'           => 'query',
            'custom'            => 'custom',
            'x-raw'             => '{"response":"ok"}',
            'x-method'          => 'GET',
            'cookies'           => 'cookies',
            'files'             => [
                'file_1' => [
                    'name'      => 'file_name',
                    'type'      => 'text',
                    'tmp_name'  => 'tmp_name',
                    'error'     => 0,
                    'size'      => 0,
                ],
            ],
        ], $this->request->all());
    }

    public function testCanThrowErrorWhenBodyEmpty(): void
    {
        $request = new Request('test.test', [], [], [], [], [], ['content-type' => 'app/json'], 'PUT', '::1', '');

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Request body is empty.');

        $request->all();
    }

    public function testCanThrowErrorWhenBodyCantDecode(): void
    {
        $request = new Request('test.test', [], [], [], [], [], ['content-type' => 'app/json'], 'PUT', '::1', 'nobody');

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Could not decode request body.');

        $request->all();
    }

    public function testCanAccessAsArrayGet(): void
    {
        $this->assertEquals('query', $this->request['query_1']);
        $this->assertNull($this->request['query_x']);
    }

    public function testCanAccessAsArrayHas(): void
    {
        $this->assertTrue(isset($this->request['query_1']));
        $this->assertFalse(isset($this->request['query_x']));
    }

    public function testCanAccessUsingGetter(): void
    {
        $this->assertEquals('query', $this->request->query_1);
    }

    public function testCanDetectAjaxRequest(): void
    {
        $req = new Request('test.test', [], [], [], [], [], [
            'X-Requested-With' => 'XMLHttpRequest',
        ]);

        $this->assertTrue($req->isAjax());
    }

    public function testCanGetItemFromAttribute(): void
    {
        $this->assertEquals('custom', $this->request->getAttribute('custom', 'fixed'));
        $this->assertEquals('fixed', $this->request->getAttribute('fixed', 'fixed'));
    }

    public function testCanUseForeachRequest(): void
    {
        foreach ($this->request as $key => $value) {
            $this->assertEquals($value, $this->request[$key]);
        }
    }

    public function testCanDetectRequestJsonRequest(): void
    {
        $this->assertFalse($this->request->isJson());
        $this->assertTrue($this->putRequest->isJson());
    }

    public function testCanReturnBodyIfRequestComeFromJsonRequest(): void
    {
        $this->assertEquals('ok', $this->putRequest->json()->get('response', 'bad'));
        $this->assertEquals('ok', $this->putRequest->all()['response']);
        $this->assertEquals('ok', $this->putRequest['response']);
    }

    public function testCanGetAllPropertyIfMethodPost(): void
    {
        $this->assertEquals([
            'header_1'          => 'header',
            'header_2'          => 123,
            'foo'               => 'bar',
            'query_1'           => 'query',
            'post_1'            => 'post',
            'custom'            => 'custom',
            'x-raw'             => '{"response":"ok"}',
            'x-method'          => 'POST',
            'cookies'           => 'cookies',
            'files'             => [
                'file_1' => [
                    'name'      => 'file_name',
                    'type'      => 'text',
                    'tmp_name'  => 'tmp_name',
                    'error'     => 0,
                    'size'      => 0,
                ],
                'file_2' => [
                    'name'      => 'test123.txt',
                    'type'      => 'file',
                    'tmp_name'  => $this->fixturePath('/fixtures/application-read/upload/test123.tmp'),
                    'error'     => 0,
                    'size'      => 1,
                ],
            ],
        ], $this->postRequest->all());
    }

    public function testCanUseValidateMacro(): void
    {
        Request::macro(
            'validate',
            fn (?Closure $rule = null, ?Closure $filter = null) => Validator::make($this->{'all'}(), $rule, $filter)
        );

        $v = $this->request->validate();
        $v->field('query_1')->required();
        $this->assertTrue($v->isValid());

        $v = $this->postRequest->validate();
        $v->field('query_1')->required();
        $v->field('post_1')->required();
        $this->assertTrue($v->isValid());

        $v = $this->postRequest->validate();
        $v->field('query_1')->required();
        $v->field('post_1')->required();
        $v->field('files.file_1')->required();
        $this->assertTrue($v->isValid());

        $v = $this->putRequest->validate();
        $v->field('response')->required();
        $this->assertTrue($v->isValid());

        $v = $this->request->validate(
            fn (ValidPool $vr) => $vr('query_1')->required(),
            fn (FilterPool $fr) => $fr('query_1')->upper_case()
        );
        $this->assertTrue($v->isValid());
        $this->assertEquals('QUERY', $v->filters->get('query_1'));
    }

    public function testCanUseUploadMacro(): void
    {
        $postRequest = $this->postRequest;

        Request::macro('upload', function (string $file_name) use ($postRequest) {
            $files = $postRequest->getFile();

            $file = $files[$file_name] ?? null;

            if (!is_array($file)) {
                throw new Exception('No uploaded file was found for the name [' . $file_name . '].');
            }

            return new UploadFile([
                'name'     => $file['name'],
                'type'     => $file['type'],
                'tmp_name' => $file['tmp_name'],
                'error'    => $file['error'],
                'size'     => $file['size'],
            ])->markTest(true);
        });

        $upload = $postRequest->upload('file_2');
        $upload
            ->setFileName('success')
            ->setFileTypes(['txt', 'md'])
            ->setFolderLocation($this->fixturePath('/fixtures/application-write/upload/'))
            ->setMaxFileSize(91)
            ->setMimeTypes(['file'])
        ;

        $upload->upload();

        $upload->delete($this->fixturePath('/fixtures/application-write/upload/success.txt'));

        $this->assertTrue($upload->success());
    }

    public function testCanModifyRequest(): void
    {
        $request  = new Request(
            'test.test',
            ['query' => 'old'],
            [],
            [],
            [],
            [],
            ['content-type' => 'app/json'],
            'PUT',
            '::1',
            ''
        );
        $request2 = $request->duplicate(['query' => 'new']);

        $this->assertEquals('old', $request->getQuery('query'));
        $this->assertEquals('new', $request2->getQuery('query'));
    }

    public function testCanGetMimeType(): void
    {
        $request = new Request(
            'test.test',
            ['query' => 'old'],
            [],
            [],
            [],
            [],
            ['content-type' => 'app/json'],
            'PUT',
            '::1',
            ''
        );

        $mimetypes = $request->getMimeTypes('html');

        $this->assertEquals(['text/html', 'application/xhtml+xml'], $mimetypes);

        $mimetypes = $request->getMimeTypes('php');

        $this->assertEquals([], $mimetypes);
    }

    public function testCanGetFormat(): void
    {
        $request = new Request(
            'test.test',
            ['query' => 'old'],
            [],
            [],
            [],
            [],
            ['content-type' => 'app/json'],
            'PUT',
            '::1',
            ''
        );

        $format = $request->getFormat('text/html');

        $this->assertEquals('html', $format);

        $format = $request->getFormat('text/php');

        $this->assertNull($format);
    }

    public function testCanGetRequestFormat(): void
    {
        $request = new Request(
            'test.test',
            ['query' => 'old'],
            [],
            [],
            [],
            [],
            ['content-type' => 'application/json'],
            'PUT',
            '::1',
            ''
        );

        $this->assertEquals('json', $request->getRequestFormat());
    }

    public function testCanNotGetRequestFormat(): void
    {
        $request = new Request('test.test', ['query' => 'old'], [], [], [], [], [], 'PUT', '::1', '');

        $this->assertNull($request->getRequestFormat());
    }

    public function testCanGetHeaderAuthorization(): void
    {
        $request = new Request('test.test', headers: ['Authorization' => '123']);

        $this->assertEquals('123', $request->getAuthorization());
    }

    public function testCanGetHeaderBearerAuthorization(): void
    {
        $request = new Request('test.test', headers: ['Authorization' => 'Bearer 123']);

        $this->assertEquals('123', $request->getBearerToken());
    }
}
