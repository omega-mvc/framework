<?php

declare(strict_types=1);

namespace Tests\Testing;

use Omega\Http\Response;
use Omega\Testing\TestJsonResponse;
use PHPUnit\Framework\TestCase as PHPUnitTestCase;

final class TmpPathProbeTest extends PHPUnitTestCase
{
    /** @return array<array-key, mixed> */
    private function expectedData(): array
    {
        return [];
    }

    private function response(): TestJsonResponse
    {
        return new TestJsonResponse(new Response(['data' => $this->expectedData(), 'status' => 'ok']));
    }

    /**
     * @param array<array-key, mixed> $expected
     * @param callable(TestJsonResponse): void $operation
     */
    private function assertData(array $expected, callable $operation): void
    {
        $response = $this->response();
        $operation($response);

        $this->assertSame($expected, $response->getData());
    }

    public function testStringOffset(): void
    {
        $this->assertData($this->expectedData(), static function (TestJsonResponse $response): void {
            $response->offsetSet('key', 'value');
            $response->offsetUnset('key');
        });
    }

    public function testIntegerOffset(): void
    {
        $this->assertData($this->expectedData(), static function (TestJsonResponse $response): void {
            $response->offsetSet(1, 'value');
            $response->offsetUnset(1);
        });
    }

    public function testFloatOffset(): void
    {
        $this->assertData($this->expectedData(), static function (TestJsonResponse $response): void {
            $response->offsetSet(1.5, 'value');
            $response->offsetUnset(1.5);
        });
    }

    public function testTrueOffset(): void
    {
        $this->assertData($this->expectedData(), static function (TestJsonResponse $response): void {
            $response->offsetSet(true, 'value');
            $response->offsetUnset(true);
        });
    }

    public function testFalseOffset(): void
    {
        $this->assertData($this->expectedData(), static function (TestJsonResponse $response): void {
            $response->offsetSet(false, 'value');
            $response->offsetUnset(false);
        });
    }

    public function testNullOffset(): void
    {
        $this->assertData($this->expectedData(), static function (TestJsonResponse $response): void {
            $response->offsetSet(null, 'value');
            $response->offsetUnset(null);
        });
    }

    public function testArrayOffset(): void
    {
        $this->assertData($this->expectedData(), static function (TestJsonResponse $response): void {
            $response->offsetSet([], 'value');
            $response->offsetUnset([]);
        });
    }

    public function testObjectOffset(): void
    {
        $this->assertData($this->expectedData(), static function (TestJsonResponse $response): void {
            $object = new \stdClass();
            $response->offsetSet($object, 'value');
            $response->offsetUnset($object);
        });
    }

    public function testResourceOffset(): void
    {
        $resource = fopen('php://memory', 'rb');

        if ($resource === false) {
            throw new \RuntimeException('Unable to open memory stream');
        }

        $this->assertData($this->expectedData(), static function (TestJsonResponse $response) use ($resource): void {
            $response->offsetSet($resource, 'value');
            $response->offsetUnset($resource);
        });

        fclose($resource);
    }
}
