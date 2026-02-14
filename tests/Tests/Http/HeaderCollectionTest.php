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
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Omega\Http\HeaderCollection;

/**
 * HeaderCollectionTest class.
 *
 * Tests the behavior of the HeaderCollection class, including adding, removing,
 * retrieving, and formatting HTTP headers and their directives.
 *
 * @category  Tests
 * @package   Http
 * @link      https://omega-mvc.github.io
 * @author    Adriano Giovannini <agisoftt@gmail.com>
 * @copyright Copyright (c) 2025 - 2026 Adriano Giovannini (https://omega-mvc.github.io)
 * @license   https://www.gnu.org/licenses/gpl-3.0-standalone.html     GPL V3.0+
 * @version   2.0.0
 */
#[CoversClass(HeaderCollection::class)]
final class HeaderCollectionTest extends TestCase
{
    /**
     * Test it can get string of header.
     *
     * @return void
     */
    public function testItCanGetStringOfHeader(): void
    {
        $header = new HeaderCollection([
            'Cache-Control' => 'max-age=31536000, public, no-transform, proxy-revalidate, s-maxage=2592000',
        ]);
        $this->assertEquals('Cache-Control: max-age=31536000, public, no-transform, proxy-revalidate, s-maxage=2592000', (string) $header);

        // with multi value
        $header = new HeaderCollection([
            'Cache-Control' => 'no-cache="http://example.com, http://example2.com"',
        ]);
        $this->assertEquals('Cache-Control: no-cache="http://example.com, http://example2.com"', (string) $header, 'with multi value');
    }

    /**
     * Test it can add raw header.
     *
     * @return void
     * @throws Exception Throw when a generic error occurred.
     */
    public function testItCanAddRawHeader(): void
    {
        $header = new HeaderCollection([]);
        $header->setRaw('Cache-Control: max-age=31536000, public, no-transform, proxy-revalidate, s-maxage=2592000');
        $this->assertEquals('Cache-Control: max-age=31536000, public, no-transform, proxy-revalidate, s-maxage=2592000', (string) $header);
    }

    /**
     * Test it can get header item directly.
     *
     * @return void
     */
    public function testItCanGetHeaderItemDirectly(): void
    {
        $header = new HeaderCollection([
            'Cache-Control' => 'max-age=31536000, public, no-transform, proxy-revalidate, s-maxage=2592000',
        ]);

        $this->assertEquals([
            'max-age' => '31536000',
            'public',
            'no-transform',
            'proxy-revalidate',
            's-maxage'=> '2592000',
        ], $header->getDirective('Cache-Control'));
    }

    /**
     * Test it can get header item directly multi value.
     *
     * @return void
     */
    public function testItCanGetHeaderItemDirectlyMultiValue(): void
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

    /**
     * Test it can add header item directly.
     *
     * @return void
     */
    public function testItCanAddHeaderItemDirectly(): void
    {
        $header = new HeaderCollection([
            'Cache-Control' => 'max-age=31536000, public, no-transform',
        ]);
        $header->addDirective('Cache-Control', ['proxy-revalidate', 's-maxage'=>'2592000']);

        $this->assertEquals([
            'max-age' => '31536000',
            'public',
            'no-transform',
            'proxy-revalidate',
            's-maxage'=> '2592000',
        ], $header->getDirective('Cache-Control'));
    }

    /**
     * Test it can remove header item directly.
     *
     * @return void
     */
    public function testItCanRemoveHeaderItemDirectly(): void
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

    /**
     * Test it can check header item directly.
     *
     * @return void
     */
    public function testItCanCheckHeaderItemDirectly(): void
    {
        $header = new HeaderCollection([
            'Cache-Control' => 'max-age=31536000, public, no-transform, proxy-revalidate, s-maxage=2592000',
        ]);

        $this->assertTrue($header->hasDirective('Cache-Control', 'proxy-revalidate'));
        $this->assertTrue($header->hasDirective('Cache-Control', 's-maxage'));
        $this->assertFalse($header->hasDirective('Cache-Control', 'private'));
    }
}
