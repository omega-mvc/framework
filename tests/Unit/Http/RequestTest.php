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

use function class_exists;
use function is_array;
use function ltrim;
use function Omega\Application\slash;

covers(Request::class);
covers(UploadFile::class);

function fixturePath(string $path = ''): string
{
    return slash(dirname(__DIR__) . ($path !== '' ? '/' . ltrim($path, '/') : ''));
}

beforeEach(function (): void {
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
                'tmp_name'  => fixturePath('/fixtures/application-read/upload/test123.tmp'),
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
});

it('has same url', function (): void {
    expect($this->request->getUrl())->toEqual('http://localhost/');
});

it('has same query', function (): void {
    expect($this->request->getQuery('query_1'))->toEqual('query');
    expect($this->request->query()->get('query_1'))->toEqual('query');
});

it('has same post', function (): void {
    expect($this->request->getPost('post_1'))->toEqual('post');
    expect($this->request->post()->get('post_1'))->toEqual('post');
});

it('has same cookies', function (): void {
    expect($this->request->getCookie('cookies'))->toEqual('cookies');
});

it('has same file', function (): void {
    $file = $this->request->getFile('file_1');

    expect($file['name'])->toEqual('file_name');
    expect($file['type'])->toEqual('text');
    expect($file['tmp_name'])->toEqual('tmp_name');
    expect($file['error'])->toEqual(0);
    expect($file['size'])->toEqual(0);
});

it('has same header', function (): void {
    expect($this->request->getHeaders('header_1'))->toEqual('header');
});

it('has same method', function (): void {
    expect($this->request->getMethod())->toEqual('GET');
});

it('has same ip', function (): void {
    expect($this->request->getRemoteAddress())->toEqual('127:0:0:1');
});

it('has same body', function (): void {
    expect($this->request->getRawBody())->toEqual('{"response":"ok"}');
});

it('has same body json', function (): void {
    expect($this->request->getJsonBody())->toEqual(['response' => 'ok']);
});

it('is not secure request', function (): void {
    expect($this->request->isSecured())->toBeFalse();
});

it('has header', function (): void {
    expect($this->request->hasHeader('header_2'))->toBeTrue();
});

it('is header contains', function (): void {
    expect($this->request->isHeader('foo', 'bar'))->toBeTrue();
});

it('can get all property', function (): void {
    expect($this->request->all())->toEqual([
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
    ]);
});

it('can throw error when body empty', function (): void {
    $request = new Request('test.test', [], [], [], [], [], ['content-type' => 'app/json'], 'PUT', '::1', '');

    expect(fn () => $request->all())->toThrow(Exception::class, 'Request body is empty.');
});

it('can throw error when body cant decode', function (): void {
    $request = new Request('test.test', [], [], [], [], [], ['content-type' => 'app/json'], 'PUT', '::1', 'nobody');

    expect(fn () => $request->all())->toThrow(Exception::class, 'Could not decode request body.');
});

it('can access as array get', function (): void {
    expect($this->request['query_1'])->toEqual('query');
    expect($this->request['query_x'])->toBeNull();
});

it('can access as array has', function (): void {
    expect(isset($this->request['query_1']))->toBeTrue();
    expect(isset($this->request['query_x']))->toBeFalse();
});

it('can access using getter', function (): void {
    expect($this->request->query_1)->toEqual('query');
});

it('can detect ajax request', function (): void {
    $req = new Request('test.test', [], [], [], [], [], [
        'X-Requested-With' => 'XMLHttpRequest',
    ]);

    expect($req->isAjax())->toBeTrue();
});

it('can get item from attribute', function (): void {
    expect($this->request->getAttribute('custom', 'fixed'))->toEqual('custom');
    expect($this->request->getAttribute('fixed', 'fixed'))->toEqual('fixed');
});

it('can use foreach request', function (): void {
    foreach ($this->request as $key => $value) {
        expect($this->request[$key])->toEqual($value);
    }
});

it('can detect request json request', function (): void {
    expect($this->request->isJson())->toBeFalse();
    expect($this->putRequest->isJson())->toBeTrue();
});

it('can return body if request come from json request', function (): void {
    expect($this->putRequest->json()->get('response', 'bad'))->toEqual('ok');
    expect($this->putRequest->all()['response'])->toEqual('ok');
    expect($this->putRequest['response'])->toEqual('ok');
});

it('can get all property if method post', function (): void {
    expect($this->postRequest->all())->toEqual([
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
                'tmp_name'  => fixturePath('/fixtures/application-read/upload/test123.tmp'),
                'error'     => 0,
                'size'      => 1,
            ],
        ],
    ]);
});

it('can use validate macro', function (): void {
    Request::macro(
        'validate',
        fn (?Closure $rule = null, ?Closure $filter = null) => Validator::make($this->{'all'}(), $rule, $filter)
    );

    $v = $this->request->validate();
    $v->field('query_1')->required();
    expect($v->isValid())->toBeTrue();

    $v = $this->postRequest->validate();
    $v->field('query_1')->required();
    $v->field('post_1')->required();
    expect($v->isValid())->toBeTrue();

    $v = $this->postRequest->validate();
    $v->field('query_1')->required();
    $v->field('post_1')->required();
    $v->field('files.file_1')->required();
    expect($v->isValid())->toBeTrue();

    $v = $this->putRequest->validate();
    $v->field('response')->required();
    expect($v->isValid())->toBeTrue();

    $v = $this->request->validate(
        fn (ValidPool $vr) => $vr('query_1')->required(),
        fn (FilterPool $fr) => $fr('query_1')->upper_case()
    );
    expect($v->isValid())->toBeTrue();
    expect($v->filters->get('query_1'))->toEqual('QUERY');
});

it('can use upload macro', function (): void {
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
        ->setFolderLocation(fixturePath('/fixtures/application-write/upload/'))
        ->setMaxFileSize(91)
        ->setMimeTypes(['file'])
    ;

    $upload->upload();

    $upload->delete(fixturePath('/fixtures/application-write/upload/success.txt'));

    expect($upload->success())->toBeTrue();
});

it('can modify request', function (): void {
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

    expect($request->getQuery('query'))->toEqual('old');
    expect($request2->getQuery('query'))->toEqual('new');
});

it('can get mime type', function (): void {
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

    expect($mimetypes)->toEqual(['text/html', 'application/xhtml+xml']);

    $mimetypes = $request->getMimeTypes('php');

    expect($mimetypes)->toEqual([]);
});

it('can get format', function (): void {
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

    expect($format)->toEqual('html');

    $format = $request->getFormat('text/php');

    expect($format)->toBeNull();
});

it('can get request format', function (): void {
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

    expect($request->getRequestFormat())->toEqual('json');
});

it('can not get request format', function (): void {
    $request = new Request('test.test', ['query' => 'old'], [], [], [], [], [], 'PUT', '::1', '');

    expect($request->getRequestFormat())->toBeNull();
});

it('can get header authorization', function (): void {
    $request = new Request('test.test', headers: ['Authorization' => '123']);

    expect($request->getAuthorization())->toEqual('123');
});

it('can get header bearer authorization', function (): void {
    $request = new Request('test.test', headers: ['Authorization' => 'Bearer 123']);

    expect($request->getBearerToken())->toEqual('123');
});
