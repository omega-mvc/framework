<?php

declare(strict_types=1);

namespace Tests\Console\Traits;

use Omega\Console\Traits\InteractsWithConsoleOutputTrait;
use Symfony\Component\Console\Output\BufferedOutput;
use Tests\Console\Fixtures\OutputStyler;
use Tests\TestCase;

use function rtrim;
use function str_repeat;
use function strlen;

use PHPUnit\Framework\Attributes\After;
use PHPUnit\Framework\Attributes\AfterClass;
use PHPUnit\Framework\Attributes\Before;
use PHPUnit\Framework\Attributes\BeforeClass;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;

final class InteractsWithConsoleOutputTest extends TestCase
{
    public function testReturnsTheTerminalWidth(): void
    {
        $styler = new OutputStyler(new BufferedOutput());

        $this->assertGreaterThan(0, $styler->terminalWidth());
    }

    public function testCalculatesTheVisibleWidthIgnoringFormattingTags(): void
    {
        $styler = new OutputStyler(new BufferedOutput());

        $this->assertSame(5, $styler->visibleWidth('plain'));
        $this->assertSame(6, $styler->visibleWidth('<info>tagged</info>'));
        $this->assertSame(3, $styler->visibleWidth("\033[31mred\033[0m"));
        $this->assertSame(0, $styler->visibleWidth(''));
    }

    public function testReturnsTheLargestVisibleWidthOfACollection(): void
    {
        $styler = new OutputStyler(new BufferedOutput());

        $this->assertSame(6, $styler->visibleMaxWidth(['foo', 'longer', '<info>x</info>']));
        $this->assertSame(0, $styler->visibleMaxWidth([]));
    }

    public function testAlignsAMessageToTheRightEdgeWithTheGivenMargin(): void
    {
        $output = new BufferedOutput();
        $styler = new OutputStyler($output);

        $styler->printRight('bye');
        $display = $output->fetch();

        $this->assertStringEndsWith(PHP_EOL, $display);
        $this->assertStringEndsWith('bye', rtrim($display, PHP_EOL));
        $this->assertSame($styler->terminalWidth() - 2, strlen(rtrim($display, PHP_EOL)));
    }

    public function testRespectsACustomRightMargin(): void
    {
        $output = new BufferedOutput();
        $styler = new OutputStyler($output);

        $styler->printRight('bye', 6);
        $display = rtrim($output->fetch(), PHP_EOL);

        $this->assertStringEndsWith('bye', $display);
        $this->assertSame($styler->terminalWidth() - 6, strlen($display));
    }

    public function testDoesNotPadWhenTheMessageIsWiderThanTheTerminal(): void
    {
        $output = new BufferedOutput();
        $styler = new OutputStyler($output);
        $message = str_repeat('x', $styler->terminalWidth() + 100);

        $styler->printRight($message);

        $this->assertSame($message, rtrim($output->fetch(), PHP_EOL));
    }

    public function testWritesTwoColumnsSeparatedByDottedFiller(): void
    {
        $output = new BufferedOutput();
        $styler = new OutputStyler($output);

        $styler->printColumns('Config', 'Done');

        $display = rtrim($output->fetch(), PHP_EOL);

        $this->assertStringStartsWith('  Config ', $display);
        $this->assertStringEndsWith(' Done  ', $display);
        $this->assertStringContainsString(str_repeat('.', 10), $display);
    }

    public function testKeepsAtLeastTwoDotsWhenTheContentFillsTheLine(): void
    {
        $output = new BufferedOutput();
        $styler = new OutputStyler($output);
        $left = str_repeat('l', 60);
        $right = str_repeat('r', 60);

        $styler->printColumns($left, $right);

        $display = rtrim($output->fetch(), PHP_EOL);

        $this->assertStringStartsWith('  ' . $left, $display);
        $this->assertStringEndsWith($right . '  ', $display);
        $this->assertStringContainsString('..', $display);
    }
}
