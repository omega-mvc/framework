<?php

declare(strict_types=1);

namespace Tests\Http;

use Omega\Http\HeaderCollection;
use PHPUnit\Framework\Attributes\CoversClass;
use Tests\TestCase;

#[CoversClass(HeaderCollection::class)]
final class HeaderCollectionTest extends TestCase
{
    public function testCanGetStringOfHeader(): void
    {
        $header = new HeaderCollection([
            'Cache-Control' => 'max-age=31536000, public, no-transform, proxy-revalidate, s-maxage=2592000',
        ]);
        $this->assertEquals(
            'Cache-Control: max-age=31536000, public, no-transform, proxy-revalidate, s-maxage=2592000',
            (string) $header
        );

        // with multi value
        $header = new HeaderCollection([
            'Cache-Control' => 'no-cache="http://example.com, http://example2.com"',
        ]);
        $this->assertEquals(
            'Cache-Control: no-cache="http://example.com, http://example2.com"',
            (string) $header
        );
    }

    public function testCanAddRawHeader(): void
    {
        $header = new HeaderCollection([]);
        $header->setRaw('Cache-Control: max-age=31536000, public, no-transform, proxy-revalidate, s-maxage=2592000');
        $this->assertEquals(
            'Cache-Control: max-age=31536000, public, no-transform, proxy-revalidate, s-maxage=2592000',
            (string) $header
        );
    }

    public function testCanGetHeaderItemDirectly(): void
    {
        $header = new HeaderCollection([
            'Cache-Control' => 'max-age=31536000, public, no-transform, proxy-revalidate, s-maxage=2592000',
        ]);

        $this->assertEquals([
            'max-age' => '31536000',
            'public',
            'no-transform',
            'proxy-revalidate',
            's-maxage' => '2592000',
        ], $header->getDirective('Cache-Control'));
    }

    public function testCanGetHeaderItemDirectlyMultiValue(): void
    {
        $header = new HeaderCollection([
            'Cache-Control' => 'no-cache="http://example.com, http://example2.com"',
        ]);

        $this->assertEquals([
            'no-cache' => [
                'http://example.com',
                'http://example2.com',
            ],
        ], $header->getDirective('Cache-Control'));
    }

    public function testCanAddHeaderItemDirectly(): void
    {
        $header = new HeaderCollection([
            'Cache-Control' => 'max-age=31536000, public, no-transform',
        ]);
        $header->addDirective('Cache-Control', ['proxy-revalidate', 's-maxage' => '2592000']);

        $this->assertEquals([
            'max-age' => '31536000',
            'public',
            'no-transform',
            'proxy-revalidate',
            's-maxage' => '2592000',
        ], $header->getDirective('Cache-Control'));
    }

    public function testCanRemoveHeaderItemDirectly(): void
    {
        $header = new HeaderCollection([
            'Cache-Control' => 'max-age=31536000, public, no-transform, proxy-revalidate, s-maxage=2592000',
        ]);
        $header->removeDirective('Cache-Control', 's-maxage');
        $header->removeDirective('Cache-Control', 'public');

        $this->assertEquals([
            'max-age' => '31536000',
            'no-transform',
            'proxy-revalidate',
        ], $header->getDirective('Cache-Control'));
    }

    public function testCanCheckHeaderItemDirectly(): void
    {
        $header = new HeaderCollection([
            'Cache-Control' => 'max-age=31536000, public, no-transform, proxy-revalidate, s-maxage=2592000',
        ]);

        $this->assertTrue($header->hasDirective('Cache-Control', 'proxy-revalidate'));
        $this->assertTrue($header->hasDirective('Cache-Control', 's-maxage'));
        $this->assertFalse($header->hasDirective('Cache-Control', 'private'));
    }
}
