<?php

declare(strict_types=1);

namespace Tests\Console\IO;

use Omega\Console\Exceptions\InvalidStreamException;
use PHPUnit\Framework\TestCase;
use Omega\Console\IO\OutputStream;

use function fclose;
use function fopen;
use function rewind;
use function stream_get_contents;

class OutputStreamTest extends TestCase
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
