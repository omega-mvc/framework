<?php

declare(strict_types=1);

namespace Tests\Console;

use Omega\Console\Style;
use PHPUnit\Framework\Attributes\CoversClass;
use RuntimeException;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;
use Tests\TestCase;

#[CoversClass(Style::class)]
final class StyleTest extends TestCase
{
    public function testWritesAnIndentedSingleLine(): void
    {
        $output = new BufferedOutput();
        $style = new Style(new ArrayInput([]), $output);

        $style->writeln('alpha');

        $this->assertSame('  alpha' . PHP_EOL, $output->fetch());
    }

    public function testWritesMultipleLinesKeepingEmptyLinesUnindented(): void
    {
        $output = new BufferedOutput();
        $style = new Style(new ArrayInput([]), $output);

        $style->writeln(['alpha', '', 'beta']);

        $this->assertSame('  alpha' . PHP_EOL . PHP_EOL . '  beta' . PHP_EOL, $output->fetch());
    }

    public function testTrimsTrailingLineEndingsBeforeIndenting(): void
    {
        $output = new BufferedOutput();
        $style = new Style(new ArrayInput([]), $output);

        $style->writeln("alpha\r\n");

        $this->assertSame('  alpha' . PHP_EOL, $output->fetch());
    }

    public function testRendersAllStyledMessageBlocks(): void
    {
        $output = new BufferedOutput();
        $style = new Style(new ArrayInput([]), $output);

        $style->success('done');
        $style->error('bad');
        $style->warning('careful');
        $style->comment('see note');
        $style->note('remember');
        $style->info('known');

        $display = $output->fetch();

        $this->assertStringContainsString('SUCCESS  done', $display);
        $this->assertStringContainsString('ERROR  bad', $display);
        $this->assertStringContainsString('WARNING  careful', $display);
        $this->assertStringContainsString('COMMENT see note', $display);
        $this->assertStringContainsString('NOTE  remember', $display);
        $this->assertStringContainsString('INFO  known', $display);
    }

    public function testAcceptsIterableMessagesInStyledBlocks(): void
    {
        $output = new BufferedOutput();
        $style = new Style(new ArrayInput([]), $output);

        $style->success(['first', 'second']);

        $display = $output->fetch();

        $this->assertStringContainsString('first', $display);
        $this->assertStringContainsString('second', $display);
    }

    public function testInsertsABlankLineBetweenBlocksThatEndWithContent(): void
    {
        $output = new BufferedOutput();
        $style = new Style(new ArrayInput([]), $output);

        $style->writeln('plain');
        $style->success('after');

        $this->assertStringContainsString('  plain' . PHP_EOL . PHP_EOL . '   SUCCESS', $output->fetch());
    }

    public function testRendersTitlesSectionsAndText(): void
    {
        $output = new BufferedOutput();
        $style = new Style(new ArrayInput([]), $output);

        $style->title('Title');
        $style->section('Body');
        $style->text('hidden note');
        $style->text(['visible line']);

        $display = $output->fetch();

        $this->assertStringContainsString('Title', $display);
        $this->assertStringContainsString('== Body ==', $display);
        $this->assertStringContainsString('  hidden note', $display);
        $this->assertStringContainsString('  visible line', $display);
    }

    public function testReturnsTheAnswerProvidedByTheUser(): void
    {
        $input = new ArrayInput([]);
        $input->setStream(self::streamWith("Ada\n"));
        $output = new BufferedOutput();
        $style = new Style($input, $output);

        $this->assertSame('Ada', $style->ask('Your name?', 'default'));
    }

    public function testConfirmsWhenTheUserAnswersYes(): void
    {
        $input = new ArrayInput([]);
        $input->setStream(self::streamWith("yes\n"));
        $output = new BufferedOutput();
        $style = new Style($input, $output);

        $this->assertTrue($style->confirm('Continue?'));
    }

    public function testFallsBackToTheDefaultWhenNoAnswerIsProvided(): void
    {
        $input = new ArrayInput([]);
        $input->setStream(self::streamWith(''));
        $output = new BufferedOutput();
        $style = new Style($input, $output);

        $this->assertFalse($style->confirm('Continue?', false));
    }

    public function testWritesTheRequestedNumberOfBlankLines(): void
    {
        $output = new BufferedOutput();
        $style = new Style(new ArrayInput([]), $output);

        $style->newLine(2);

        $this->assertSame(PHP_EOL . PHP_EOL, $output->fetch());
    }

    public function testCreatesAProgressBarWithAMessage(): void
    {
        $output = new BufferedOutput();
        $style = new Style(new ArrayInput([]), $output);

        $bar = $style->progressBar(10, 'Working');

        $this->assertSame(10, $bar->getMaxSteps());
        $this->assertSame('Working', $bar->getMessage());

        $output->fetch();
    }

    public function testCreatesAProgressBarWithoutAMessage(): void
    {
        $output = new BufferedOutput();
        $style = new Style(new ArrayInput([]), $output);

        $bar = $style->progressBar(0);

        $this->assertSame(0, $bar->getMaxSteps());
        $this->assertNull($bar->getMessage());

        $output->fetch();
    }

    public function testWritesNothingWhenGivenAnEmptyMessagesArray(): void
    {
        $output = new BufferedOutput();
        $style = new Style(new ArrayInput([]), $output);

        $style->writeln([]);

        $this->assertSame('', $output->fetch());
    }

    public function testDoesNotIndentAnEmptyStringLine(): void
    {
        $output = new BufferedOutput();
        $style = new Style(new ArrayInput([]), $output);

        $style->writeln('');

        $this->assertSame(PHP_EOL, $output->fetch());
    }

    public function testRendersAnEmptyIterableMessageInAStyledBlock(): void
    {
        $output = new BufferedOutput();
        $style = new Style(new ArrayInput([]), $output);

        $style->success([]);

        $this->assertSame('   SUCCESS  ' . PHP_EOL . PHP_EOL, $output->fetch());
    }

    /**
     * @return resource
     */
    private static function streamWith(string $content)
    {
        $stream = fopen('php://memory', 'r+');
        if ($stream === false) {
            throw new RuntimeException('Unable to open in-memory stream.');
        }
        fwrite($stream, $content);
        rewind($stream);

        return $stream;
    }
}
