<?php

declare(strict_types=1);

namespace Tests\Http;

use Omega\Http\JsonResponse;
use Tests\TestCase;

use function json_decode;
use function ob_get_clean;
use function ob_start;

use const JSON_FORCE_OBJECT;
use const JSON_HEX_AMP;
use const JSON_HEX_APOS;
use const JSON_HEX_QUOT;
use const JSON_HEX_TAG;

final class JsonResponseTest extends TestCase
{
    public function testCanRenderJsonString(): void
    {
        $response = new JsonResponse([
            'language' => 'php',
            'ver'      => 80,
        ]);

        ob_start();
        $response->send();
        $json = ob_get_clean();

        $this->assertIsString($json);

        $this->assertNotNull(json_decode($json));
        $data = json_decode($json, true);
        $this->assertEquals('{"language":"php","ver":80}', $response->getContent());
        $this->assertEquals($data, $response->getData());
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('application/json', $response->getContentType());
    }

    public function testWillThrowsInvalidException(): void
    {
        $this->expectExceptionMessageIsOrContains('Invalid encode data.');
        new JsonResponse(['say' => "Hello \x80 World"]);
    }

    public function testConstructorEmptyCreatesJsonObject(): void
    {
        $response = new JsonResponse();
        $this->assertSame('{}', $response->getContent());
    }

    public function testConstructorWithArrayCreatesJsonArray(): void
    {
        $response = new JsonResponse([0, 1, 2, 3]);
        $this->assertSame('[0,1,2,3]', $response->getContent());
    }

    public function testSetJson(): void
    {
        $response = new JsonResponse();
        $response->setJson('1');
        $this->assertEquals('1', $response->getContent());

        $response = new JsonResponse();
        $response->setJson('true');
        $this->assertEquals('true', $response->getContent());
    }

    public function testJsonEncodeFlags(): void
    {
        $response = new JsonResponse();
        $response->setData('<>\'&"');

        $this->assertEquals('"\u003C\u003E\u0027\u0026\u0022"', $response->getContent());
    }

    public function testGetEncodingOptions(): void
    {
        $response = new JsonResponse();

        $this->assertEquals(
            JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT,
            $response->getEncodingOptions()
        );
    }

    public function testCanSetEncodingOptions(): void
    {
        $response = new JsonResponse();
        $response->setData([[1, 2, 3]]);

        $this->assertEquals('[[1,2,3]]', $response->getContent());

        $response->setEncodingOptions(JSON_FORCE_OBJECT);

        $this->assertEquals('{"0":{"0":1,"1":2,"2":3}}', $response->getContent());
    }
}
