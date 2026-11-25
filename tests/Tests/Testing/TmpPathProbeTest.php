<?php

declare(strict_types=1);

namespace Tests\Testing;

use Omega\Http\Response;
use Omega\Testing\TestJsonResponse;
use PHPUnit\Framework\TestCase as PHPUnitTestCase;

final class TmpPathProbeTest extends PHPUnitTestCase
{
    private function response(): TestJsonResponse
    {
        return new TestJsonResponse(new Response(['data' => [], 'status' => 'ok']));
    }

    public function testStringOffset(): void
    {
        $response = $this->response();
        $response->offsetSet('key', 'value');
        $response->offsetUnset('key');

        $this->assertTrue(true);
    }

    public function testIntegerOffset(): void
    {
        $response = $this->response();
        $response->offsetSet(1, 'value');
        $response->offsetUnset(1);

        $this->assertTrue(true);
    }

    public function testFloatOffset(): void
    {
        $response = $this->response();
        $response->offsetSet(1.5, 'value');
        $response->offsetUnset(1.5);

        $this->assertTrue(true);
    }

    public function testTrueOffset(): void
    {
        $response = $this->response();
        $response->offsetSet(true, 'value');
        $response->offsetUnset(true);

        $this->assertTrue(true);
    }

    public function testFalseOffset(): void
    {
        $response = $this->response();
        $response->offsetSet(false, 'value');
        $response->offsetUnset(false);

        $this->assertTrue(true);
    }

    public function testNullOffset(): void
    {
        $response = $this->response();
        $response->offsetSet(null, 'value');
        $response->offsetUnset(null);

        $this->assertTrue(true);
    }

    public function testArrayOffset(): void
    {
        $response = $this->response();
        $response->offsetSet([], 'value');
        $response->offsetUnset([]);

        $this->assertTrue(true);
    }

    public function testObjectOffset(): void
    {
        $response = $this->response();
        $response->offsetSet(new \stdClass(), 'value');
        $response->offsetUnset(new \stdClass());

        $this->assertTrue(true);
    }

    public function testResourceOffset(): void
    {
        $resource = \fopen('php://memory', 'rb');
        $response  = $this->response();
        $response->offsetSet($resource, 'value');
        $response->offsetUnset($resource);
        \fclose($resource);

        $this->assertTrue(true);
    }
}
