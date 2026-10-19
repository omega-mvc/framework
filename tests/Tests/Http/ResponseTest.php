<?php

declare(strict_types=1);

namespace Tests\Http;

use Omega\Http\Request;
use Omega\Http\Response;
use Omega\Text\Str;
use PHPUnit\Framework\Attributes\CoversClass;
use Tests\TestCase;

use function json_decode;
use function ob_get_clean;
use function ob_start;
use function rand;

#[CoversClass(Request::class)]
#[CoversClass(Response::class)]
#[CoversClass(Str::class)]
final class ResponseTest extends TestCase
{
    private Response $htmlResponse;

    private Response $jsonResponse;

    protected function setUp(): void
    {
        parent::setUp();

        $html = '<html lang="en"><head></head><body></body></html>';
        $json = [
            'status'  => 'ok',
            'code'    => 200,
            'data'    => null,
        ];

        $this->htmlResponse = new Response($html, 200, []);
        $this->jsonResponse = new Response($json, 200, []);
    }

    public function testRenderHtmlResponse(): void
    {
        ob_start();
        $this->htmlResponse->html()->send();
        $html = ob_get_clean();

        $this->assertEquals(
            '<html lang="en"><head></head><body></body></html>',
            $html
        );
    }

    public function testRenderJsonResponse(): void
    {
        ob_start();
        $this->jsonResponse->json()->send();
        $json = ob_get_clean();

        $this->assertIsString($json);

        $this->assertNotNull(json_decode($json));
        $this->assertEquals(
            [
                'status'  => 'ok',
                'code'    => 200,
                'data'    => null,
            ],
            json_decode($json, true)
        );
    }

    public function testCanBeEditedContent(): void
    {
        $this->htmlResponse->setContent('edited');

        ob_start();
        $this->htmlResponse->html()->send();
        $html = ob_get_clean();

        $this->assertEquals(
            'edited',
            $html
        );
    }

    public function testCanSetHeaderUsingConstructHeader(): void
    {
        $res = new Response('content', 200, ['test' => 'test']);

        $get_header = $res->getHeaders()['test'];

        $this->assertEquals('test', $get_header);
    }

    public function testCanSetHeaderUsingSetHeaders(): void
    {
        $res = new Response('content');
        $res->setHeaders(['test' => 'test']);

        $get_header = $res->getHeaders()['test'];

        $this->assertEquals('test', $get_header);
    }

    public function testCanSetHeaderUsingHeader(): void
    {
        $res = new Response('content');
        $res->header('test', 'test');

        $get_header = $res->getHeaders()['test'];

        $this->assertEquals('test', $get_header);
    }

    public function testCanSetHeaderUsingHeaderAndSanitizerHeader(): void
    {
        $res = new Response('content');
        $res->header('test : test:ok');

        $get_header = $res->getHeaders()['test'];

        $this->assertEquals('test:ok', $get_header);
    }

    public function testCanSetHeaderUsingFollowRequest(): void
    {
        $req = new Request('test', [], [], [], [], [], ['test' => 'test']);
        $res = new Response('content');

        $res->followRequest($req, ['test']);
        $get_header = $res->getHeaders()['test'];

        $this->assertEquals('test', $get_header);
    }

    public function testCanGetResponseStatusCode(): void
    {
        $res = new Response('content', 200);

        $this->assertEquals(200, $res->getStatusCode());
    }

    public function testCanGetResponseContent(): void
    {
        $res = new Response('content', 200);

        $this->assertEquals('content', $res->getContent());
    }

    public function testCanGetTypeOfResponseCode(): void
    {
        $res = new Response('content', rand(100, 199));
        $this->assertTrue($res->isInformational());

        $res = new Response('content', rand(200, 299));
        $this->assertTrue($res->isSuccessful());

        $res = new Response('content', rand(300, 399));
        $this->assertTrue($res->isRedirection());

        $res = new Response('content', rand(400, 499));
        $this->assertTrue($res->isClientError());

        $res = new Response('content', rand(500, 599));
        $this->assertTrue($res->isServerError());
    }

    public function testCanChangeProtocolVersion(): void
    {
        $res = new Response('content');
        $res->setProtocolVersion('1.0');

        $this->assertTrue(Str::contains((string) $res, '1.0'));
    }
}
