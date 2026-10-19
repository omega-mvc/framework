<?php

declare(strict_types=1);

namespace Tests\DocBlockGenerator\VarExport;

use Omega\DocBlockGenerator\VarExport;
use PHPUnit\Framework\Attributes\CoversClass;
use Tests\TestCase;

#[CoversClass(VarExport::class)]
final class BufferManagementTest extends TestCase
{
    public function testStartsEmptyBuffer(): void
    {
        $varExport = new VarExport();
        $result    = $varExport->export([]);

        $this->assertEquals('[]', $result);
    }

    public function testResetsBufferAfterCompile(): void
    {
        $varExport = new VarExport();

        $varExport->export(['foo' => 'bar']);

        $result = $varExport->export([]);

        $this->assertEquals('[]', $result);
    }
}
