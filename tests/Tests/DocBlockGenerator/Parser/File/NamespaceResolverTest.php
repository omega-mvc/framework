<?php

declare(strict_types=1);

namespace Tests\DocBlockGenerator\Parser\File;

use Omega\DocBlockGenerator\Parser\File\NamespaceResolver;
use PHPUnit\Framework\Attributes\CoversClass;
use Tests\TestCase;

#[CoversClass(NamespaceResolver::class)]
final class NamespaceResolverTest extends TestCase
{
    public function testResolveFileMissing(): void
    {
        $resolver = new NamespaceResolver();

        $this->assertSame([], $resolver->resolveFile('/tmp/opencode/definitely-missing-nsfile.php'));
    }

    public function testResolveFileExisting(): void
    {
        $file = (string) tempnam(sys_get_temp_dir(), 'nsfile');
        try {
            file_put_contents($file, '<?php use Omega\\Http\\Request;');
            $resolver = new NamespaceResolver();

            $this->assertSame(['Omega\\Http\\Request'], $resolver->resolveFile($file));
        } finally {
            @unlink($file);
        }
    }

    public function testParsesUseStatements(): void
    {
        $sources = <<<'PHP'
        <?php
        use Omega\Http\Request;
        use Omega\Http\Response as Res;
        use Omega\Router\Route, Omega\Router\Router;
        PHP;

        $resolver = new NamespaceResolver();

        $this->assertSame([
            'Omega\Http\Request',
            'Omega\Http\Response',
            'Omega\Router\Route',
            'Omega\Router\Router',
        ], $resolver->resolve($sources));
    }

    public function testParsesGroupUseStatements(): void
    {
        $sources = <<<'PHP'
        <?php
        use Omega\Http\{Request as Req, Response};
        use Omega\Util\{function f, const C, Helper};
        PHP;

        $resolver = new NamespaceResolver();

        $this->assertSame([
            'Omega\Http\RequestReq',
            'Omega\Http\Response',
            'Omega\Util\f',
            'Omega\Util\C',
            'Omega\Util\Helper',
        ], $resolver->resolve($sources));
    }

    public function testResolveClassesSimpleAliasMultiAndModifiers(): void
    {
        $sources = <<<'PHP'
        <?php
        use function Omega\Http\f;
        use const Omega\Http\C;
        use Omega\Http\Request as Req, Omega\Router\Route;
        PHP;

        $resolver = new NamespaceResolver();

        $this->assertSame([
            'Omega\Http\Request',
            'Omega\Router\Route',
        ], $resolver->resolveClasses($sources));
    }

    public function testResolveClassesGrouped(): void
    {
        $sources = <<<'PHP'
        <?php
        use Omega\Util\{function f, const C, Helper, Other as O};
        PHP;

        $resolver = new NamespaceResolver();

        $this->assertSame([
            'Omega\Util\Helper',
            'Omega\Util\OtherO',
        ], $resolver->resolveClasses($sources));
    }

    public function testHandlesAFileWithNoUseStatements(): void
    {
        $resolver = new NamespaceResolver();

        $this->assertSame([], $resolver->resolve('<?php class X {}'));
        $this->assertSame([], $resolver->resolveClasses('<?php class X {}'));
    }

    public function testClosureUseIsIgnored(): void
    {
        $sources = '<?php $x = 1; $f = function () use ($x) {}; use A\B;';
        $resolver = new NamespaceResolver();

        $this->assertSame(['A\B'], $resolver->resolve($sources));
        $this->assertSame(['A\B'], $resolver->resolveClasses($sources));
    }

    public function testNoSemicolonDropsImport(): void
    {
        $sources = '<?php use A\B';
        $resolver = new NamespaceResolver();

        $this->assertSame([], $resolver->resolve($sources));
        $this->assertSame([], $resolver->resolveClasses($sources));
    }

    public function testGroupedWithoutClosingBraceKeepsCompletedSegments(): void
    {
        $sources = '<?php use A\{B, C';
        $resolver = new NamespaceResolver();

        $this->assertSame(['A\B'], $resolver->resolve($sources));
        $this->assertSame(['A\B'], $resolver->resolveClasses($sources));
    }

    public function testEmptyUseStatement(): void
    {
        $sources = '<?php use ;';
        $resolver = new NamespaceResolver();

        $this->assertSame([''], $resolver->resolve($sources));
        $this->assertSame([], $resolver->resolveClasses($sources));
    }

    public function testEmptyGroupedSegments(): void
    {
        $sources = '<?php use A\{, B, };';
        $resolver = new NamespaceResolver();

        $this->assertSame(['A\B'], $resolver->resolve($sources));
        $this->assertSame(['A\B'], $resolver->resolveClasses($sources));
    }

    public function testDuplicatesAreRemoved(): void
    {
        $sources = '<?php use A\B; use A\B;';
        $resolver = new NamespaceResolver();

        $this->assertSame(['A\B'], $resolver->resolve($sources));
        $this->assertSame(['A\B'], $resolver->resolveClasses($sources));
    }
}
