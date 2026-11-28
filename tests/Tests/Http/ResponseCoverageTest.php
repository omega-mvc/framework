<?php

declare(strict_types=1);

namespace Tests\Http;

use Omega\Http\Request;
use Omega\Http\Response;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\PreserveGlobalState;
use PHPUnit\Framework\Attributes\RunInSeparateProcess;
use Tests\Http\Support\ResponseNonCommandLine;
use Tests\Http\Support\ResponseWithSentHeaders;
use Tests\TestCase;

use function ob_end_clean;
use function ob_get_clean;
use function ob_get_level;
use function ob_start;

#[CoversClass(Response::class)]
final class ResponseCoverageTest extends TestCase
{
    public function testToStringRendersArrayContentAndUnknownStatus(): void
    {
        $response = new Response(['a' => 1], 999);

        $text = (string) $response;

        $this->assertStringContainsString('HTTP/1.1 999 ok', $text);
        $this->assertStringContainsString('{"a":1}', $text);
    }

    public function testJsonOverridesContentAndContentType(): void
    {
        $response = new Response('old');

        $this->assertSame($response, $response->json(['a' => 1]));
        $this->assertSame('application/json', $response->getContentType());
        $this->assertSame(['a' => 1], $response->getContent());
    }

    public function testJsonKeepsContentWhenNull(): void
    {
        $response = new Response('kept');

        $response->json(null);

        $this->assertSame('kept', $response->getContent());
        $this->assertSame('application/json', $response->getContentType());
    }

    public function testHtmlMinifiesStringContent(): void
    {
        $response = new Response("<div>  a  </div>\n<!-- comment -->");

        $response->html(true);

        $content = $response->getContent();
        $this->assertIsString($content);
        $this->assertStringNotContainsString('<!--', $content);
        $this->assertStringNotContainsString('  ', $content);
        $this->assertSame('text/html', $response->getContentType());
    }

    public function testHtmlSkipsMinifyForArrayContent(): void
    {
        $response = new Response(['a' => 1]);

        $response->html(true);

        $this->assertSame(['a' => 1], $response->getContent());
    }

    public function testPlainTextSetsContentType(): void
    {
        $response = new Response('plain');

        $this->assertSame($response, $response->plainText());
        $this->assertSame('text/html', $response->getContentType());
    }

    public function testSetContentType(): void
    {
        $response = new Response('content');

        $this->assertSame($response, $response->setContentType('application/xml'));
        $this->assertSame('application/xml', $response->getContentType());
    }

    public function testRemoveHeaderWithStringAndArray(): void
    {
        $response = new Response('content');

        $this->assertSame($response, $response->removeHeader('X-String'));
        $this->assertSame($response, $response->removeHeaders([]));
        $this->assertSame($response, $response->removeHeaders(['X-Array']));
        $this->assertSame($response, $response->removeHeader(['X-Legacy']));
    }

    public function testRemoveDefaultHeaderAndCustomHeadersDuringSend(): void
    {
        $response = new Response('body', 200, ['X-Keep' => 'yes']);
        $response->removeDefaultHeader(true);
        $response->removeHeader('X-Remove');

        ob_start();
        $response->send();
        $output = ob_get_clean();

        $this->assertSame('body', $output);
    }

    public function testGetters(): void
    {
        $response = new Response('body', 404, ['X-A' => 'a']);
        $response->setProtocolVersion('1.0');

        $this->assertSame('body', $response->getContent());
        $this->assertSame(404, $response->getStatusCode());
        $this->assertSame('1.0', $response->getProtocolVersion());
        $this->assertSame('text/html', $response->getContentType());
        $this->assertSame('a', $response->getHeaders()['X-A']);
    }

    public function testSetHeadersHandlesEmptyAssociativeRawAndInvalidEntries(): void
    {
        $response = new Response('content');

        $this->assertSame($response, $response->setHeaders([]));
        $response->setHeaders(['X-A' => 'a']);
        $this->assertSame('a', $response->getHeaders()['X-A']);

        $response->setHeaders([0 => 'X-B: b']);
        $this->assertSame('b', $response->getHeaders()['X-B']);

        $response->setHeaders([0 => 'invalid-header']);
        $this->assertArrayNotHasKey('invalid-header', $response->getHeaders());
    }

    public function testHeaderRawValue(): void
    {
        $response = new Response('content');

        $response->header('X-Raw: value');

        $this->assertSame('value', $response->getHeaders()['X-Raw']);
    }

    public function testFollowRequestCopiesPresentHeadersOnly(): void
    {
        $request  = new Request('test', [], [], [], [], [], [
            'test'          => 'test',
            'cache-control' => 'no-cache',
        ]);
        $response = new Response('content');

        $this->assertSame($response, $response->followRequest($request, ['test', 'missing']));

        $headers = $response->getHeaders();
        $this->assertSame('test', $headers['test']);
        $this->assertSame('no-cache', $headers['cache-control']);
        $this->assertArrayNotHasKey('missing', $headers);
        $this->assertArrayNotHasKey('content-type', $headers);
    }

    public function testStatusCheckBoundaries(): void
    {
        $this->assertTrue((new Response('', 100))->isInformational());
        $this->assertFalse((new Response('', 201))->isInformational());
        $this->assertTrue((new Response('', 200))->isSuccessful());
        $this->assertFalse((new Response('', 301))->isSuccessful());
        $this->assertTrue((new Response('', 300))->isRedirection());
        $this->assertFalse((new Response('', 401))->isRedirection());
        $this->assertTrue((new Response('', 400))->isClientError());
        $this->assertFalse((new Response('', 501))->isClientError());
        $this->assertTrue((new Response('', 500))->isServerError());
        $this->assertFalse((new Response('', 601))->isServerError());
    }

    public function testCloseOutputBuffersCleansBuffers(): void
    {
        $level = ob_get_level();
        ob_start();
        ob_start();
        echo 'discarded';

        Response::closeOutputBuffers($level, false);

        $this->assertSame($level, ob_get_level());
    }

    public function testCloseOutputBuffersFlushesBuffers(): void
    {
        ob_start();
        $level = ob_get_level();
        ob_start();
        echo 'flushed';

        Response::closeOutputBuffers($level, true);
        $this->assertSame($level, ob_get_level());

        $output = ob_get_clean();
        $this->assertSame('flushed', $output);
    }

    public function testCloseOutputBuffersStopsAtUnmatchedBuffer(): void
    {
        $level = ob_get_level();
        ob_start(null, 0, PHP_OUTPUT_HANDLER_STDFLAGS & ~PHP_OUTPUT_HANDLER_FLUSHABLE);

        Response::closeOutputBuffers($level, true);

        $this->assertSame($level + 1, ob_get_level());
        ob_end_clean();
    }

    public function testCloseOutputBuffersWithUnreachableTargetLevel(): void
    {
        Response::closeOutputBuffers(ob_get_level() + 5, false);
        Response::closeOutputBuffers(ob_get_level() + 5, true);

        $this->assertGreaterThan(0, ob_get_level());
    }

    public function testSendSkipsHeadersWhenAlreadySent(): void
    {
        $response = new ResponseWithSentHeaders('body');

        ob_start();
        $response->send();
        $output = ob_get_clean();

        $this->assertSame('body', $output);
    }

    public function testSendFlushesOutputOnNonCommandLine(): void
    {
        $response = new ResponseNonCommandLine('body');

        ob_start(null, 0, PHP_OUTPUT_HANDLER_STDFLAGS & ~PHP_OUTPUT_HANDLER_FLUSHABLE);
        ob_start();
        $this->assertSame($response, $response->send());
        $output = ob_get_clean();

        $this->assertSame('body', $output);
    }

    #[RunInSeparateProcess]
    #[PreserveGlobalState(false)]
    public function testSendUsesFastcgiFinishRequest(): void
    {
        require_once __DIR__ . '/Support/fastcgi_finish_request.php';

        ob_start();
        (new Response('body'))->send();
        $output = ob_get_clean();

        $this->assertSame('body', $output);
        $this->assertTrue(fastcgi_finish_request_called());
    }

    #[RunInSeparateProcess]
    #[PreserveGlobalState(false)]
    public function testSendUsesLitespeedFinishRequest(): void
    {
        require_once __DIR__ . '/Support/litespeed_finish_request.php';

        ob_start();
        (new Response('body'))->send();
        $output = ob_get_clean();

        $this->assertSame('body', $output);
        $this->assertTrue(litespeed_finish_request_called());
    }
}
