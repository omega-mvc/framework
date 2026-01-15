<?php

declare(strict_types=1);

namespace Tests\Console;

use Omega\Console\AbstractCommand;
use Omega\Console\IO\OutputStream;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

use function fclose;
use function fopen;
use function putenv;
use function rewind;
use function stream_get_contents;
use function stream_isatty;

use const STDOUT;

#[CoversClass(AbstractCommand::class)]
#[CoversClass(OutputStream::class)]
class CommandTest extends TestCase
{
    /**
     * Sets up the environment before each test method.
     *
     * This method is called automatically by PHPUnit before each test runs.
     * It is responsible for initializing the application instance, setting up
     * dependencies, and preparing any state required by the test.
     *
     * @return void
     */
    protected function setUp(): void
    {
        $this->resetEnv();
    }

    private function resetEnv(): void
    {
        foreach (['NO_COLOR', 'TERM', 'TERM_PROGRAM', 'COLORTERM', 'ANSICON', 'ConEmuANSI', 'MSYSTEM'] as $var) {
            putenv($var);
        }
    }

    /**
     * Test it can get width.
     *
     * @return void
     */
    public function testItCanGetWidth(): void
    {
        $command = new class ([]) extends AbstractCommand {
            public function width(int $min = 80, int $max = 160): int
            {
                return $this->getWidth($min, $max);
            }
        };

        $width = $command->width();
        $this->assertIsInt($width);
        $this->assertGreaterThan(79, $width);
        $this->assertLessThan(161, $width);
    }

    /**
     * Test it can get width using column.
     *
     * @return void
     */
    public function testItCanGetWidthUsingColumn(): void
    {
        $_ENV['COLUMNS'] = '100';
        $command         = new class ([]) extends AbstractCommand {
            public function width(int $min = 80, int $max = 160): int
            {
                return $this->getWidth($min, $max);
            }
        };

        $width = $command->width();
        $this->assertEquals(80, $width);
    }

    /**
     * Test it disable when no color.
     *
     * @return void
     */
    public function testItDisablesWhenNoColor(): void
    {
        $cmd = new class ([]) extends AbstractCommand {
            public function color($stream = STDOUT): bool
            {
                return $this->hasColorSupport($stream);
            }
        };

        putenv('NO_COLOR=1');
        $this->assertFalse($cmd->color());
    }

    /**
     * Test it matches term pattern.
     *
     * @return void
     */
    public function testItMatchesTermPattern(): void
    {
        if (!@stream_isatty(STDOUT)) {
            $this->markTestSkipped('Not a TTY, TERM match pattern test skipped');
        }

        $cmd = new class ([]) extends AbstractCommand {
            public function color($stream = STDOUT): bool
            {
                return $this->hasColorSupport($stream);
            }
        };

        putenv('TERM=xterm-256color');
        $this->assertTrue($cmd->color());
    }

    /**
     * Test it disables when term dumb.
     *
     * @return void
     */
    public function testItDisablesWhenTermDumb(): void
    {
        $cmd = new class ([]) extends AbstractCommand {
            public function color($stream = STDOUT): bool
            {
                return $this->hasColorSupport($stream);
            }
        };

        putenv('TERM=dumb');
        $fp = fopen('php://temp', 'w'); // not a TTY
        $this->assertFalse($cmd->color($fp));
    }

    /**
     * Test constructing the OutputStream with valid stream.
     *
     * @return void
     */
    public function testPrintOutputUsingResource(): void
    {
        $stream       = fopen('php://memory', 'w+');
        $outputStream = new OutputStream($stream);

        $command = new class ($outputStream) extends AbstractCommand {
            public function __construct(OutputStream $output)
            {
                parent::__construct([]);
                $this->outputStream = $output;
            }

            public function printTest(): void
            {
                $this->output($this->outputStream, [
                    'colorize' => false,
                    'decorate' => false,
                ])->push('Hello, World!')->write(false);
            }
        };

        $command->printTest();
        rewind($stream);
        $this->assertEquals('Hello, World!', stream_get_contents($stream));

        fclose($stream);
    }
}
