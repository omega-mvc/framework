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

    public function testGetDirectiveHandlesMissingAndNonStringHeaders(): void
    {
        $header = new HeaderCollection([
            'X' => 'public',
        ]);

        $this->assertSame([], $header->getDirective('Missing'));

        $header->set('Broken', 123);
        $this->assertSame([], $header->getDirective('Broken'));
    }

    public function testGetDirectiveHandlesEmptyAndSingleDirectives(): void
    {
        $header = new HeaderCollection([
            'X' => '',
        ]);
        $this->assertSame([''], $header->getDirective('X'));

        $header = new HeaderCollection([
            'X' => 'public',
        ]);
        $this->assertSame(['public'], $header->getDirective('X'));

        $header = new HeaderCollection([
            'X' => 'max-age=10',
        ]);
        $this->assertSame(['max-age' => '10'], $header->getDirective('X'));
    }

    public function testGetDirectiveKeepsUnterminatedQuotedValue(): void
    {
        $header = new HeaderCollection([
            'X' => 'no-cache="unterminated',
        ]);

        $this->assertSame(['no-cache' => '"unterminated'], $header->getDirective('X'));
    }

    public function testAddDirectiveQuotesArrayValues(): void
    {
        $header = new HeaderCollection([]);
        $header->addDirective('Cache-Control', [
            'no-cache' => ['http://a.test', 'http://b.test'],
        ]);

        $this->assertSame('no-cache="http://a.test, http://b.test"', $header->get('Cache-Control'));
        $this->assertSame([
            'no-cache' => ['http://a.test', 'http://b.test'],
        ], $header->getDirective('Cache-Control'));
    }

    public function testAddDirectiveAppendsNumericKeysAndAcceptsEmptyInput(): void
    {
        $header = new HeaderCollection([
            'X' => 'public',
        ]);
        $header->addDirective('X', ['private']);
        $this->assertSame('public, private', $header->get('X'));

        $header->addDirective('X', []);
        $this->assertSame('public, private', $header->get('X'));
    }

    public function testRemoveDirectiveMatchesKeysValuesAndMissingHeader(): void
    {
        $header = new HeaderCollection([
            'X' => 'max-age=10',
        ]);
        $header->removeDirective('X', 'max-age');
        $this->assertSame('', $header->get('X'));

        $header = new HeaderCollection([
            'X' => 'public',
        ]);
        $header->removeDirective('X', 'public');
        $this->assertSame('', $header->get('X'));

        $header = new HeaderCollection([
            'X' => 'public',
        ]);
        $header->removeDirective('X', 'private');
        $this->assertSame('public', $header->get('X'));

        $header->removeDirective('Missing', 'public');
        $this->assertSame('', $header->get('Missing'));
    }

    public function testHasDirectiveChecksKeysValuesAndEmptyHeaders(): void
    {
        $header = new HeaderCollection([
            'X' => 'max-age=10, public',
        ]);

        $this->assertTrue($header->hasDirective('X', 'max-age'));
        $this->assertTrue($header->hasDirective('X', 'public'));
        $this->assertFalse($header->hasDirective('X', 'private'));
        $this->assertFalse($header->hasDirective('Missing', 'public'));
    }
}
