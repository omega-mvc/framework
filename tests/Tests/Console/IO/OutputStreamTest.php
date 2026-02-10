<?php

/**
 * Part of Omega - Tests\Console Package.
 *
 * @link      https://omega-mvc.github.io
 * @author    Adriano Giovannini <agisoftt@gmail.com>
 * @copyright Copyright (c) 2025 - 2026 Adriano Giovannini (https://omega-mvc.github.io)
 * @license   https://www.gnu.org/licenses/gpl-3.0-standalone.html     GPL V3.0+
 * @version   2.0.0
 */

declare(strict_types=1);

namespace Tests\Console\IO;

use Omega\Console\Exceptions\InvalidStreamException;
use Omega\Console\IO\OutputStream;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

use function fclose;
use function fopen;
use function rewind;
use function stream_get_contents;

/**
 * Class OutputStreamTest
 *
 * Provides unit tests for the `OutputStream` class, verifying its behavior
 * when handling different types of streams. Tests include:
 *
 * - Constructing with a valid writable stream
 * - Handling invalid or non-writable streams with exceptions
 * - Writing data to a stream and validating its contents
 * - Checking whether the stream is interactive
 *
 * This test ensures that `OutputStream` correctly validates input streams
 * and reliably writes output as expected.
 *
 * @category   Tests
 * @package    Console
 * @subpackage IO
 * @link       https://omega-mvc.github.io
 * @author     Adriano Giovannini <agisoftt@gmail.com>
 * @copyright  Copyright (c) 2025 - 2026 Adriano Giovannini
 * @license    https://www.gnu.org/licenses/gpl-3.0-standalone.html GPL V3.0+
 * @version    2.0.0
 */
#[CoversClass(InvalidStreamException::class)]
#[CoversClass(OutputStream::class)]
final class OutputStreamTest extends TestCase
{
    /**
     * Test constructor with valid stream.
     *
     * @return void
     */
    public function testConstructorWithValidStream(): void
    {
        $stream       = fopen('php://memory', 'w+');
        $outputStream = new OutputStream($stream);

        /** @noinspection PhpConditionAlreadyCheckedInspection */
        $this->assertInstanceOf(OutputStream::class, $outputStream);
        fclose($stream);
    }

    /**
     * Test constructor throws exception for invalid stream.
     *
     * @return void
     */
    public function testConstructorThrowsForInvalidStream(): void
    {
        $this->expectException(InvalidStreamException::class);
        $this->expectExceptionMessage('Expected a valid stream');

        new OutputStream('invalid_stream');
    }

    /**
     * Test constructor throws exception for non-writable stream.
     *
     * @return void
     */
    public function testConstructorThrowsForNonWritableStream(): void
    {
        $stream = fopen('php://memory', 'r');

        $this->expectException(InvalidStreamException::class);
        $this->expectExceptionMessage('Expected a writable stream');

        new OutputStream($stream);

        fclose($stream);
    }

    /**
     * Test writing to a valid stream.
     *
     * @return void
     */
    public function testWriteToStream(): void
    {
        $stream       = fopen('php://memory', 'w+');
        $outputStream = new OutputStream($stream);

        $outputStream->write('Hello, World!');

        rewind($stream);
        $this->assertEquals('Hello, World!', stream_get_contents($stream));

        fclose($stream);
    }

    /**
     * Test if the stream is interactive.
     *
     * @return void
     */
    public function testIsInteractive(): void
    {
        $stream       = fopen('php://memory', 'w+');
        $outputStream = new OutputStream($stream);

        $this->assertFalse($outputStream->isInteractive());

        fclose($stream);
    }
}
