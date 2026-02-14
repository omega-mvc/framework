<?php

/**
 * Part of Omega - Tests\Http Package.
 *
 * @link      https://omega-mvc.github.io
 * @author    Adriano Giovannini <agisoftt@gmail.com>
 * @copyright Copyright (c) 2025 - 2026 Adriano Giovannini (https://omega-mvc.github.io)
 * @license   https://www.gnu.org/licenses/gpl-3.0-standalone.html     GPL V3.0+
 * @version   2.0.0
 */

declare(strict_types=1);

namespace Tests\Http;

use Closure;
use Exception;
use Omega\Http\Request;
use Omega\Http\Upload\UploadFile;
use Omega\Validator\Rule\FilterPool;
use Omega\Validator\Rule\ValidPool;
use Omega\Validator\Validator;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Tests\FixturesPathTrait;
use Throwable;

use function class_exists;

/**
 * Tests the Request HTTP abstraction.
 *
 * Verifies URL, query, post, files, headers, JSON handling,
 * macros, duplication, validation, upload behavior, and
 * format detection across different HTTP methods.
 *
 * @category  Tests
 * @package   Http
 * @link      https://omega-mvc.github.io
 * @author    Adriano Giovannini <agisoftt@gmail.com>
 * @copyright Copyright (c) 2025 - 2026 Adriano Giovannini (https://omega-mvc.github.io)
 * @license   https://www.gnu.org/licenses/gpl-3.0-standalone.html     GPL V3.0+
 * @version   2.0.0
 */
#[CoversClass(Request::class)]
#[CoversClass(UploadFile::class)]
final class RequestTest extends TestCase
{
    use FixturesPathTrait;

    /** Base GET request instance used for generic request assertions. */
    private Request $request;

    /** POST request instance with multiple files for upload-related tests. */
    private Request $postRequest;

    /** PUT JSON request instance used to test JSON body handling. */
    private Request $putRequest;

    /**
     * Sets up the environment before each test method.
     *
     * This method is called automatically by PHPUnit before each test runs.
     * It is responsible for initializing the application instance, setting up
     * dependencies, and preparing any state required by the test.
     *
     * @return void
     */
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
            ['header_1'  => 'header', 'header_2' => 123, 'foo' => 'bar'],
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
                    'tmp_name'  => $this->setFixturePath(slash(path: '/fixtures/application-read/upload/test123.tmp')),
                    'error'     => 0,
                    'size'      => 1,
                ],
            ],
            ['header_1'  => 'header', 'header_2' => 123, 'foo' => 'bar'],
            'POST',
            '127:0:0:1',
            '{"response":"ok"}'
        );

        $this->putRequest = new Request('test.test', [], [], [], [], [], [
            'content-type' => 'app/json',
        ], '', '', '{"response":"ok"}');
    }

    /**
     * Test has same url.
     *
     * @return void
     */
    public function testHasSameUrl(): void
    {
        $this->assertEquals('http://localhost/', $this->request->getUrl());
    }

    /**
     * Test has same query.
     *
     * @return void
     */
    public function testHasSameQuery(): void
    {
        $this->assertEquals('query', $this->request->getQuery('query_1'));
        $this->assertEquals('query', $this->request->query()->get('query_1'));
    }

    /**
     * Test has same post.
     *
     * @return void
     */
    public function testHasSamePost(): void
    {
        $this->assertEquals('post', $this->request->getPost('post_1'));
        $this->assertEquals('post', $this->request->post()->get('post_1'));
    }

    /**
     * Test has same cookies.
     *
     * @return void
     */
    public function testHasSameCookies(): void
    {
        $this->assertEquals('cookies', $this->request->getCookie('cookies'));
    }

    /**
     * Test has same file.
     *
     * @return void
     */
    public function testHasSameFile(): void
    {
        $file = $this->request->getFile('file_1');
        $this->assertEquals(
            'file_name',
            $file['name']
        );
        $this->assertEquals(
            'text',
            $file['type']
        );
        $this->assertEquals(
            'tmp_name',
            $file['tmp_name']
        );
        $this->assertEquals(
            0,
            $file['error']
        );
        $this->assertEquals(
            0,
            $file['size']
        );
    }

    /**
     * Test has same header.
     *
     * @return void
     */
    public function testHasSameHeader(): void
    {
        $this->assertEquals('header', $this->request->getHeaders('header_1'));
    }

    /**
     * Test has same method.
     *
     * @return void
     */
    public function testHasSameMethod(): void
    {
        $this->assertEquals('GET', $this->request->getMethod());
    }

    /**
     * Test has same ip.
     *
     * @return void
     */
    public function testHasSameIp(): void
    {
        $this->assertEquals('127:0:0:1', $this->request->getRemoteAddress());
    }

    /**
     * Test has same body.
     *
     * @return void
     */
    public function testHasSameBody(): void
    {
        $this->assertEquals('{"response":"ok"}', $this->request->getRawBody());
    }

    /**
     * Test has same body JSON.
     *
     * @return void
     * @throws Exception
     */
    public function testHasSameBodyJson(): void
    {
        $this->assertEquals(
            ['response' => 'ok'],
            $this->request->getJsonBody());
    }

    /**
     * Test it not secure request.
     *
     * @return void
     */
    public function testItNotSecureRequest(): void
    {
        $this->assertFalse($this->request->isSecured());
    }

    /**
     * Test has header.
     */
    public function testHasHeader(): void
    {
        $this->assertTrue($this->request->hasHeader('header_2'));
    }

    /**
     * Test is header contains.
     *
     * @return void
     */
    public function testIsHeaderContains(): void
    {
        $this->assertTrue($this->request->isHeader('foo', 'bar'));
    }

    /**
     * Test it can get all property.
     *
     * @return void
     * @throws Exception Throw when a generic error occurred.
     */
    public function testItCanGetAllProperty(): void
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

    /**
     * Test it can throw error when body empty.
     *
     * @return void
     * @throws Throwable When request body is empty and JSON decoding is expected.
     */
    public function testItCanThrowErrorWhenBodyEmpty(): void
    {
        $request = new Request('test.test', [], [], [], [], [], ['content-type' => 'app/json'], 'PUT', '::1', '');

        try {
            $request->all();
        } catch (Throwable $th) {
            $this->assertEquals('Request body is empty.', $th->getMessage());
        }
    }

    /**
     * Test it can throw error when body cont decode.
     *
     * @return void
     * @throws Throwable When request body cannot be decoded as valid JSON.
     */
    public function testItCanThrowErrorWhenBodyCantDecode(): void
    {
        $request = new Request('test.test', [], [], [], [], [], ['content-type' => 'app/json'], 'PUT', '::1', 'nobody');

        try {
            $request->all();
        } catch (Throwable $th) {
            $this->assertEquals('Could not decode request body.', $th->getMessage());
        }
    }

    /**
     * Test it can access as array get.
     *
     * @return void
     */
    public function testItCanAccessAsArrayGet(): void
    {
        $this->assertEquals('query', $this->request['query_1']);
        $this->assertEquals(null, $this->request['query_x']);
    }

    /**
     * Test it can access as array has.
     *
     * @return void
     */
    public function testItCanAccessAsArrayHas(): void
    {
        $this->assertTrue(isset($this->request['query_1']));
        $this->assertFalse(isset($this->request['query_x']));
    }

    /**
     * Test it can access using getter.
     *
     * @return void
     */
    public function testItCanAccessUsingGetter(): void
    {
        $this->assertEquals('query', $this->request->query_1);
    }

    /**
     * Test it can detect ajax request.
     *
     * @return void
     */
    public function testItCanDetectAjaxRequest(): void
    {
        $req = new Request('test.test', [], [], [], [], [], [
            'X-Requested-With' => 'XMLHttpRequest',
        ]);
        $this->assertTrue($req->isAjax());
    }

    /**
     * Test it can get item from attribute.
     *
     * @return void
     */
    public function testItCanGetItemFromAttribute(): void
    {
        $this->assertEquals('custom', $this->request->getAttribute('custom', 'fixed'));
        $this->assertEquals('fixed', $this->request->getAttribute('fixed', 'fixed'));
    }

    /**
     * Test it can use foreach request.
     *
     * @return void
     */
    public function testItCanUseForeachRequest(): void
    {
        foreach ($this->request as $key => $value) {
            $this->assertEquals($this->request[$key], $value);
        }
    }

    /**
     * Test it can detect request json request.
     *
     * @return void
     */
    public function testItCanDetectRequestJsonRequest(): void
    {
        $this->assertFalse($this->request->isJson());
        $this->assertTrue($this->putRequest->isJson());
    }

    /**
     * Test it can return body if request come from JSON request.
     *
     * @return void
     * @throws Exception Throw when a generic error occurred.
     */
    public function testItCanReturnBodyIfRequestComeFromJsonRequest(): void
    {
        $this->assertEquals('ok', $this->putRequest->json()->get('response', 'bad'));
        $this->assertEquals('ok', $this->putRequest->all()['response']);
        $this->assertEquals('ok', $this->putRequest['response']);
    }

    /**
     * Test it can get all property if method post.
     *
     * @return void
     * @throws Exception Throw when a generic error occurred.
     */
    public function testItCanGetAllPropertyIfMethodPost(): void
    {
        $this->assertEquals($this->postRequest->all(), [
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
                    'tmp_name'  => $this->setFixturePath(slash(path: '/fixtures/application-read/upload/test123.tmp')),
                    'error'     => 0,
                    'size'      => 1,
                ],
            ],
        ]);
    }

    /**
     * Test it can use validate macro.
     *
     * @return void
     */
    public function testItCanUseValidateMacro(): void
    {
        Request::macro(
            'validate',
            fn (?Closure $rule = null, ?Closure $filter = null) => Validator::make($this->{'all'}(), $rule, $filter)
        );

        // get
        $v = $this->request->validate();
        $v->field('query_1')->required();
        $this->assertTrue($v->isValid());

        // post
        $v = $this->postRequest->validate();
        $v->field('query_1')->required();
        $v->field('post_1')->required();
        $this->assertTrue($v->isValid());

        // file
        $v = $this->postRequest->validate();
        $v->field('query_1')->required();
        $v->field('post_1')->required();
        $v->field('files.file_1')->required();
        $this->assertTrue($v->isValid());

        // put
        $v = $this->putRequest->validate();
        $v->field('response')->required();
        $this->assertTrue($v->isValid());

        // get (filter)
        $v = $this->request->validate(
            fn (ValidPool $vr) => $vr('query_1')->required(),
            fn (FilterPool $fr) => $fr('query_1')->upper_case()
        );
        $this->assertTrue($v->isValid());
        $this->assertEquals('QUERY', $v->filters->get('query_1'));
    }

    /**
     * Test it can use upload macro.
     *
     * @return void
     */
    public function testItCanUseUploadMacro(): void
    {
        Request::macro(
            'upload',
            function ($file_name) {
                $files = $this->{'getFile'}();

                return new UploadFile($files[$file_name])->markTest(true);
            }
        );

        $upload = $this->postRequest->upload('file_2');
        $upload
            ->setFileName('success')
            ->setFileTypes(['txt', 'md'])
            ->setFolderLocation($this->setFixturePath(slash(path: '/fixtures/application-write/upload/')))
            ->setMaxFileSize(91)
            ->setMimeTypes(['file'])
        ;

        $upload->upload();

        $upload->delete($this->setFixturePath(slash(path: '/fixtures/application-write/upload/success.txt')));

        $this->assertTrue($upload->success());
    }

    /**
     * Test it can modify request.
     *
     * @return void
     */
    public function testItCanModifyRequest(): void
    {
        $request  = new Request('test.test', ['query' => 'old'], [], [], [], [], ['content-type' => 'app/json'], 'PUT', '::1', '');
        $request2 = $request->duplicate(['query' => 'new']);

        $this->assertEquals('old', $request->getQuery('query'));
        $this->assertEquals('new', $request2->getQuery('query'));
    }

    /**
     * Test it can get mime type.
     *
     * @return void
     */
    public function testItCanGetMimeType(): void
    {
        $request  = new Request('test.test', ['query' => 'old'], [], [], [], [], ['content-type' => 'app/json'], 'PUT', '::1', '');

        $mimetypes = $request->getMimeTypes('html');
        $this->assertEquals(['text/html', 'application/xhtml+xml'], $mimetypes);

        $mimetypes = $request->getMimeTypes('php');
        $this->assertEquals([], $mimetypes, 'php format is not exists');
    }

    /**
     * Test it can get format.
     *
     * @return void
     */
    public function testItCanGetFormat(): void
    {
        $request  = new Request('test.test', ['query' => 'old'], [], [], [], [], ['content-type' => 'app/json'], 'PUT', '::1', '');

        $format = $request->getFormat('text/html');
        $this->assertEquals('html', $format);

        $format = $request->getFormat('text/php');
        $this->assertNull($format, 'php format not exist');
    }

    /**
     * Test it can request format.
     *
     * @return void
     */
    public function testItCanGetRequestFormat(): void
    {
        $request  = new Request('test.test', ['query' => 'old'], [], [], [], [], ['content-type' => 'application/json'], 'PUT', '::1', '');

        $this->assertEquals('json', $request->getRequestFormat());
    }

    /**
     * Test it can not get request format.
     *
     * @return void
     */
    public function testItCanNotGetRequestFormat(): void
    {
        $request  = new Request('test.test', ['query' => 'old'], [], [], [], [], [], 'PUT', '::1', '');

        $this->assertNull($request->getRequestFormat());
    }

    /**
     * Test it can get header authorization.
     *
     * @return void
     */
    public function testItCanGetHeaderAuthorization(): void
    {
        $request = new Request('test.test', headers: ['Authorization' => '123']);

        $this->assertEquals('123', $request->getAuthorization());
    }

    /**
     * Test it can get header bearer authorization.
     *
     * @return void
     */
    public function testItCanGetHeaderBearerAuthorization(): void
    {
        $request = new Request('test.test', headers: ['Authorization' => 'Bearer 123']);

        $this->assertEquals('123', $request->getBearerToken());
    }
}
