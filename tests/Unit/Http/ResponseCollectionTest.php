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
use Omega\Http\HeaderCollection;
use Omega\Text\Str;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Throwable;

/**
 * Tests the behavior of HeaderCollection when generating and validating
 * HTTP header strings.
 *
 * This test suite verifies header creation from arrays, key/value pairs,
 * and raw header strings. It ensures proper string rendering and confirms
 * that invalid header structures trigger the expected exceptions.
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
#[CoversClass(Str::class)]
final class ResponseCollectionTest extends TestCase
{
    /**
     * Test it can generate header to header string.
     *
     * @return void
     */
    public function testItCanGenerateHeaderToHeaderString(): void
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

    /**
     * Test it can generate header using set with value.
     *
     * @return void
     */
    public function testItCanGenerateHeaderUsingSetWithValue(): void
    {
        $header = new HeaderCollection([]);
        $header->set('Host', 'test.test');
        $header->set('Accept', 'text/html');
        $header->set('Connection', 'keep-alive');

        $this->assertTrue(Str::contains((string) $header, 'Host: test.test'));
        $this->assertTrue(Str::contains((string) $header, 'Accept: text/htm'));
        $this->assertTrue(Str::contains((string) $header, 'Connection: keep-alive'));
    }

    /**
     * est it can generate header using set with key only.
     *
     * @return void
     * @throws Exception Throw when a generic error occurred.
     */
    public function testItCanGenerateHeaderUsingSetWithKeyOnly(): void
    {
        $header = new HeaderCollection([]);
        $header->setRaw('Host: test.test');
        $header->setRaw('Accept: text/html');
        $header->setRaw('Connection: keep-alive');

        $this->assertTrue(Str::contains((string) $header, 'Host: test.test'));
        $this->assertTrue(Str::contains((string) $header, 'Accept: text/htm'));
        $this->assertTrue(Str::contains((string) $header, 'Connection: keep-alive'));
    }

    /**
     * Test it can generate header using set with key only but throw error.
     *
     * @return void
     * @throws Exception Throw when a generic error occurred.
     * @throws Throwable Thrown when an invalid raw header format is provided.
     */
    public function testItCanGenerateHeaderUsingSetWithKeyOnlyButThrowError(): void
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
