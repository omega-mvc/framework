<?php

declare(strict_types=1);

namespace Tests\Http;

use Omega\Http\HeaderCollection;
use Omega\Text\Str;
use PHPUnit\Framework\Attributes\CoversClass;
use Tests\TestCase;
use Throwable;

#[CoversClass(HeaderCollection::class)]
#[CoversClass(Str::class)]
final class ResponseCollectionTest extends TestCase
{
    public function testCanGenerateHeaderToHeaderString(): void
    {
        $header = new HeaderCollection([
            'Host'       => 'test.test',
            'Accept'     => 'text/html',
            'Connection' => 'keep-alive',
        ]);

        $this->assertTrue(Str::contains((string) $header, 'Host: test.test'));
        $this->assertTrue(Str::contains((string) $header, 'Accept: text/htm'));
        $this->assertTrue(Str::contains((string) $header, 'Connection: keep-alive'));
    }

    public function testCanGenerateHeaderUsingSetWithValue(): void
    {
        $header = new HeaderCollection([]);
        $header->set('Host', 'test.test');
        $header->set('Accept', 'text/html');
        $header->set('Connection', 'keep-alive');

        $this->assertTrue(Str::contains((string) $header, 'Host: test.test'));
        $this->assertTrue(Str::contains((string) $header, 'Accept: text/htm'));
        $this->assertTrue(Str::contains((string) $header, 'Connection: keep-alive'));
    }

    public function testCanGenerateHeaderUsingSetWithKeyOnly(): void
    {
        $header = new HeaderCollection([]);
        $header->setRaw('Host: test.test');
        $header->setRaw('Accept: text/html');
        $header->setRaw('Connection: keep-alive');

        $this->assertTrue(Str::contains((string) $header, 'Host: test.test'));
        $this->assertTrue(Str::contains((string) $header, 'Accept: text/htm'));
        $this->assertTrue(Str::contains((string) $header, 'Connection: keep-alive'));
    }

    public function testCanGenerateHeaderUsingSetWithKeyOnlyButThrowError(): void
    {
        $header  = new HeaderCollection([]);
        $message = '';
        try {
            $header->setRaw('Host=test.test');
        } catch (Throwable $th) {
            $message = $th->getMessage();
        }

        $this->assertEquals('Invalid header structure Host=test.test.', $message);
    }
}
