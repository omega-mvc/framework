<?php

declare(strict_types=1);

namespace Tests\Console;

use Omega\Console\Test\TestCommand;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Throwable;

use function explode;

#[CoversClass(TestCommand::class)]
class ConsoleParseAsArrayTest extends TestCase
{
    /**
     * Test it can parse normal command with space.
     *
     * @return void
     */
    public function testItCanParseNormalCommandWithSpace()
    {
        $command = 'php omega test --n john -t -s --who-is children';
        $argv    = explode(' ', $command);
        $cli     = new TestCommand($argv);

        $this->assertEquals(
            'test',
            $cli['name'],
            'valid parse name: test'
        );

        $this->assertEquals(
            'john',
            $cli['n'],
            'valid parse from short param with sparte space: --n'
        );

        $this->assertTrue(
            isset($cli['who-is']),
            'valid parse from long param: --who-is'
        );
    }

    /**
     * Test it will throw exception when change command.
     *
     * @return void
     */
    public function testItWillThrowExceptionWhenChangeCommand(): void
    {
        $command = 'php omega test --n john -t -s --who-is children';
        $argv    = explode(' ', $command);
        $cli     = new TestCommand($argv);

        try {
            $cli['name'] = 'taylor';
        } catch (Throwable $th) {
            $this->assertEquals('Command cant be modify', $th->getMessage());
        }
    }

    /**
     * Test it will throw exception when unset command.
     *
     * @return void
     */
    public function testItWillThrowExceptionWhenUnsetCommand(): void
    {
        $command = 'php omega test --n john -t -s --who-is children';
        $argv    = explode(' ', $command);
        $cli     = new TestCommand($argv);

        try {
            unset($cli['name']);
        } catch (Throwable $th) {
            $this->assertEquals('Command cant be modify', $th->getMessage());
        }
    }

    /**
     * Test it can check option has exit or not.
     *
     * @return void
     */
    public function testItCanCheckOptionHasExitOrNot(): void
    {
        $command = 'php omega test --true="false"';
        $argv    = explode(' ', $command);
        $cli     = new TestCommand($argv);

        $this->assertTrue((fn () => $this->{'hasOption'}('true'))->call($cli));
        $this->assertFalse((fn () => $this->{'hasOption'}('not-exist'))->call($cli));
    }
}
