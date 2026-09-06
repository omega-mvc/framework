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

use Exception;
use Omega\Http\Request;
use Omega\Http\Response;
use Omega\Text\Str;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

use function json_decode;
use function ob_get_clean;
use function ob_start;
use function rand;

/**
 * Class ResponseTest
 *
 * This test suite validates the behavior of the Response class,
 * ensuring that it correctly handles different content types,
 * status codes, headers, and protocol versions.
 *
 * It verifies:
 * - Proper rendering of HTML and JSON responses.
 * - Content mutation after instantiation.
 * - Header management through constructor, setters, and helper methods.
 * - Header propagation from a Request instance.
 * - Accurate status code retrieval and classification
 *   (informational, successful, redirection, client error, server error).
 * - Protocol version customization and string representation.
 *
 * The goal of this test class is to guarantee that the Response
 * implementation behaves consistently and adheres to expected
 * HTTP semantics.
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
#[CoversClass(Response::class)]
#[CoversClass(Str::class)]
final class ResponseTest extends TestCase
{
    /** @var Response HTML Response instance used to test rendering and content manipulation. */
    private Response $htmlResponse;

    /** @var Response JSON Response instance used to test JSON encoding and output behavior. */
    private Response $jsonResponse;

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
        $html = '<html lang="en"><head></head><body></body></html>';
        $json = [
            'status'  => 'ok',
            'code'    => 200,
            'data'    => null,
        ];

        $this->htmlResponse = new Response($html, 200, []);
        $this->jsonResponse = new Response($json, 200, []);
    }

    /**
     * Test it render html response.
     *
     * @return void
     */
    public function testItRenderHtmlResponse(): void
    {
        ob_start();
        $this->htmlResponse->html()->send();
        $html = ob_get_clean();

        $this->assertEquals(
            '<html lang="en"><head></head><body></body></html>',
            $html
        );
    }

    /**
     * Test it render json response.
     *
     * @return void
     */
    public function testItRenderJsonResponse(): void
    {
        ob_start();
        $this->jsonResponse->json()->send();
        $json = ob_get_clean();

        $this->assertJson($json);
        $this->assertEquals(
            [
                'status'  => 'ok',
                'code'    => 200,
                'data'    => null,
            ],
            json_decode($json, true)
        );
    }

    /**
     * Test it can be edited content.
     *
     * @return void
     */
    public function testItCanBeEditedContent(): void
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

    /**
     * Test it can set header using construct header.
     *
     * @return void
     */
    public function testItCanSetHeaderUsingConstructHeader(): void
    {
        $res = new Response('content', 200, ['test' => 'test']);

        $get_header = $res->getHeaders()['test'];

        $this->assertEquals('test', $get_header);
    }

    /**
     * Test it can set header using set header.
     *
     * @return void
     * @throws Exception Throw when a generic error occurred.
     */
    public function testItCanSetHeaderUsingSetHeaders(): void
    {
        $res = new Response('content');
        $res->setHeaders(['test' => 'test']);

        $get_header = $res->getHeaders()['test'];

        $this->assertEquals('test', $get_header);
    }

    /**
     * Test it can set header using header.
     *
     * @return void
     * @throws Exception Throw when a generic error occurred.
     */
    public function testItCanSetHeaderUsingHeader(): void
    {
        $res = new Response('content');
        $res->header('test', 'test');

        $get_header = $res->getHeaders()['test'];

        $this->assertEquals('test', $get_header);
    }

    /**
     * Test it can set header using header and sanitizer header.
     *
     * @return void
     * @throws Exception Throw when a generic error occurred.
     */
    public function testItCanSetHeaderUsingHeaderAndSanitizerHeader(): void
    {
        $res = new Response('content');
        $res->header('test : test:ok');

        $get_header = $res->getHeaders()['test'];

        $this->assertEquals('test:ok', $get_header);
    }

    /**
     * Test it can set header using follow request.
     *
     * @return void
     */
    public function testItCanSetHeaderUsingFollowRequest(): void
    {
        $req = new Request('test', [], [], [], [], [], ['test' => 'test']);
        $res = new Response('content');

        $res->followRequest($req, ['test']);
        $get_header = $res->getHeaders()['test'];

        $this->assertEquals('test', $get_header);
    }

    /**
     * Test it can get response status code.
     *
     * @return void
     */
    public function testItCanGetResponseStatusCode(): void
    {
        $res = new Response('content', 200);

        $this->assertEquals(200, $res->getStatusCode());
    }

    /**
     * Test it can get response content.
     *
     * @return void
     */
    public function testItCanGetResponseContent(): void
    {
        $res = new Response('content', 200);

        $this->assertEquals('content', $res->getContent());
    }

    /**
     * Test it can get type of response code.
     *
     * @return void
     */
    public function testItCanGetTypeOfResponseCode(): void
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

    /**
     * Test it can change protocol version.
     *
     * @return void
     */
    public function testItCanChangeProtocolVersion(): void
    {
        $res = new Response('content');
        $res->setProtocolVersion('1.0');

        $this->assertTrue(Str::contains((string) $res, '1.0'), 'Test protocol version');
    }
}
