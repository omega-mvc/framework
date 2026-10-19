<?php

declare(strict_types=1);

namespace Tests\Filesystem\Stream;

use Omega\Filesystem\Stream\StreamMode;
use PHPUnit\Framework\Attributes\CoversClass;
use Tests\TestCase;

#[CoversClass(StreamMode::class)]
final class StreamModeTest extends TestCase
{
    public function testParsesReadMode(): void
    {
        $mode = new StreamMode('r');
        $this->assertSame('r', $mode->getMode());
        $this->assertTrue($mode->allowsRead());
        $this->assertFalse($mode->allowsWrite());
        $this->assertTrue($mode->allowsExistingFileOpening());
        $this->assertFalse($mode->allowsNewFileOpening());
        $this->assertFalse($mode->impliesExistingContentDeletion());
        $this->assertTrue($mode->impliesPositioningCursorAtTheBeginning());
        $this->assertFalse($mode->impliesPositioningCursorAtTheEnd());
        $this->assertFalse($mode->isBinary());
        $this->assertTrue($mode->isText());
    }

    public function testParsesWriteMode(): void
    {
        $mode = new StreamMode('w');
        $this->assertFalse($mode->allowsRead());
        $this->assertTrue($mode->allowsWrite());
        $this->assertTrue($mode->allowsExistingFileOpening());
        $this->assertTrue($mode->allowsNewFileOpening());
        $this->assertTrue($mode->impliesExistingContentDeletion());
        $this->assertTrue($mode->impliesPositioningCursorAtTheBeginning());
        $this->assertFalse($mode->impliesPositioningCursorAtTheEnd());
    }

    public function testParsesAppendMode(): void
    {
        $mode = new StreamMode('a');
        $this->assertFalse($mode->allowsRead());
        $this->assertTrue($mode->allowsWrite());
        $this->assertTrue($mode->allowsExistingFileOpening());
        $this->assertTrue($mode->allowsNewFileOpening());
        $this->assertFalse($mode->impliesExistingContentDeletion());
        $this->assertFalse($mode->impliesPositioningCursorAtTheBeginning());
        $this->assertTrue($mode->impliesPositioningCursorAtTheEnd());
    }

    public function testParsesExclusiveCreateMode(): void
    {
        $mode = new StreamMode('x');
        $this->assertFalse($mode->allowsRead());
        $this->assertTrue($mode->allowsWrite());
        $this->assertFalse($mode->allowsExistingFileOpening());
        $this->assertTrue($mode->allowsNewFileOpening());
        $this->assertFalse($mode->impliesExistingContentDeletion());
        $this->assertTrue($mode->impliesPositioningCursorAtTheBeginning());
        $this->assertFalse($mode->impliesPositioningCursorAtTheEnd());
    }

    public function testParsesReadPlusMode(): void
    {
        $mode = new StreamMode('r+');
        $this->assertTrue($mode->allowsRead());
        $this->assertTrue($mode->allowsWrite());
        $this->assertTrue($mode->allowsExistingFileOpening());
        $this->assertFalse($mode->allowsNewFileOpening());
        $this->assertFalse($mode->impliesExistingContentDeletion());
    }

    public function testParsesWritePlusMode(): void
    {
        $mode = new StreamMode('w+');
        $this->assertTrue($mode->allowsRead());
        $this->assertTrue($mode->allowsWrite());
        $this->assertTrue($mode->allowsNewFileOpening());
        $this->assertTrue($mode->impliesExistingContentDeletion());
    }

    public function testParsesAppendPlusMode(): void
    {
        $mode = new StreamMode('a+');
        $this->assertTrue($mode->allowsRead());
        $this->assertTrue($mode->allowsWrite());
        $this->assertTrue($mode->impliesPositioningCursorAtTheEnd());
    }

    public function testParsesBinaryReadMode(): void
    {
        $mode = new StreamMode('rb');
        $this->assertSame('rb', $mode->getMode());
        $this->assertTrue($mode->allowsRead());
        $this->assertTrue($mode->isBinary());
        $this->assertFalse($mode->isText());
    }

    public function testParsesBinaryWriteMode(): void
    {
        $mode = new StreamMode('wb');
        $this->assertTrue($mode->allowsWrite());
        $this->assertTrue($mode->isBinary());
    }
}
