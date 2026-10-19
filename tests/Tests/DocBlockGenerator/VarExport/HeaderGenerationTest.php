<?php

declare(strict_types=1);

namespace Tests\DocBlockGenerator\VarExport;

use Omega\DocBlockGenerator\VarExport;
use PHPUnit\Framework\Attributes\CoversClass;
use ReflectionClass;
use Tests\TestCase;

#[CoversClass(VarExport::class)]
final class HeaderGenerationTest extends TestCase
{
    public function testGeneratesHeader(): void
    {
        $exporter   = new VarExport();
        $reflection = new ReflectionClass($exporter);
        $method     = $reflection->getMethod('compileToString');
        /** @var string $output */
        $output = $method->invoke($exporter, []);

        $this->assertStringStartsWith('<?php', $output);
        $this->assertStringContainsString('declare(strict_types=1);', $output);
        $this->assertStringContainsString('// auto-generated file, do not edit!', $output);
        $this->assertStringContainsString('return ', $output);
    }
}
