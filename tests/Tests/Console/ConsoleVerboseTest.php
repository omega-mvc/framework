<?php

declare(strict_types=1);

namespace Tests\Console;

use Omega\Console\AbstractCommand;
use Omega\Console\Test\TestCommand;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

use function explode;

#[ CoversClass(AbstractCommand::class)]
#[CoversClass(TestCommand::class)]
class ConsoleVerboseTest extends TestCase
{
    /**
     * Test it can get default verbosity.
     *
     * @return void
     */
    public function testItCanGetDefaultVerbosity(): void
    {
        $command = 'php omega test';
        $argv    = explode(' ', $command);
        $cli     = new TestCommand($argv);

        $this->assertTrue(AbstractCommand::VERBOSITY_NORMAL === $cli->getVerbosity());
    }

    /**
     * Test it can set verbosity.
     *
     * @return void
     */
    public function testItCanSetVerbosity(): void
    {
        $command = 'php omega test';
        $argv    = explode(' ', $command);
        $cli     = new TestCommand($argv);
        $cli->setVerbosity(AbstractCommand::VERBOSITY_VERBOSE);

        $this->assertTrue(AbstractCommand::VERBOSITY_VERBOSE === $cli->getVerbosity());
    }

    /**
     * Test it can get verbosity silent.
     *
     * @return void
     */
    public function testItCanGetVerbositySilent(): void
    {
        $command = 'php omega test --silent';
        $argv    = explode(' ', $command);
        $cli     = new TestCommand($argv);

        $this->assertTrue($cli->isSilent());
    }

    /**
     * Test it can get verbosity quiet.
     *
     * @return void
     */
    public function testItCanGetVerbosityQuiet(): void
    {
        $command = 'php omega test --quiet';
        $argv    = explode(' ', $command);
        $cli     = new TestCommand($argv);

        $this->assertTrue($cli->isQuiet());
    }

    /**
     * Test it can get verbosity verbose.
     *
     * @return void
     */
    public function testItCanGetVerbosityVerbose(): void
    {
        $command = 'php omega test -v';
        $argv    = explode(' ', $command);
        $cli     = new TestCommand($argv);

        $this->assertTrue($cli->isVerbose());
    }

    /**
     * Test it can get verbosity very verbose.
     *
     * @return void
     */
    public function testItCanGetVerbosityVeryVerbose(): void
    {
        $command = 'php omega test -vv';
        $argv    = explode(' ', $command);
        $cli     = new TestCommand($argv);

        $this->assertTrue($cli->isVeryVerbose());
    }

    /**
     * Test it can get verbosity debug.
     *
     * @return void
     */
    public function testItCanGetVerbosityDebug(): void
    {
        $command = 'php omega test -vvv';
        $argv    = explode(' ', $command);
        $cli     = new TestCommand($argv);

        $this->assertTrue($cli->isDebug());

        $command = 'php omega test --debug';
        $argv    = explode(' ', $command);
        $cli     = new TestCommand($argv);

        $this->assertTrue($cli->isDebug());
    }

    private function getVerbosityFlags(string $command): array
    {
        $argv = explode(' ', $command);
        $cli  = new TestCommand($argv);

        return [
            'silent'       => $cli->isSilent(),
            'quiet'        => $cli->isQuiet(),
            'verbose'      => $cli->isVerbose(),
            'very-verbose' => $cli->isVeryVerbose(),
            'debug'        => $cli->isDebug(),
        ];
    }

    /**
     * Test it silent level should only enable silent flag.
     *
     * @return void
     */
    public function testItSilentLevelShouldOnlyEnableSilentFlag(): void
    {
        $this->assertEquals(
            [
                'silent'       => true,
                'quiet'        => false,
                'verbose'      => false,
                'very-verbose' => false,
                'debug'        => false,
            ],
            $this->getVerbosityFlags('php omega test --silent')
        );
    }

    /**
     * Test it quiet level should only enable quiet flag.
     *
     * @return void
     */
    public function testItQuietLevelShouldOnlyEnableQuietFlag(): void
    {
        $this->assertEquals(
            [
                'silent'       => false,
                'quiet'        => true,
                'verbose'      => false,
                'very-verbose' => false,
                'debug'        => false,
            ],
            $this->getVerbosityFlags('php omega test --quiet')
        );
    }

    /**
     * Test it normal level should only enable normal flag.
     *
     * @return void
     */
    public function testItNormalLevelShouldOnlyEnableNormalFlag(): void
    {
        $this->assertEquals(
            [
                'silent'       => false,
                'quiet'        => false,
                'verbose'      => false,
                'very-verbose' => false,
                'debug'        => false,
            ],
            $this->getVerbosityFlags('php omega test')
        );
    }

    /**
     * Test it verbose level should enable normal and verbose flags.
     *
     * @return void
     */
    public function testItVerboseLevelShouldEnableNormalAndVerboseFlags(): void
    {
        $this->assertEquals(
            [
                'silent'       => false,
                'quiet'        => false,
                'verbose'      => true,
                'very-verbose' => false,
                'debug'        => false,
            ],
            $this->getVerbosityFlags('php omega test -v')
        );
    }

    /**
     * Test it very verbose level should enable normal verbose and very verbose flags.
     *
     * @return void
     */
    public function testItVeryVerboseLevelShouldEnableNormalVerboseAndVeryVerboseFlags(): void
    {
        $this->assertEquals(
            [
                'silent'       => false,
                'quiet'        => false,
                'verbose'      => true,
                'very-verbose' => true,
                'debug'        => false,
            ],
            $this->getVerbosityFlags('php omega test -vv')
        );
    }

    /**
     * Test it debug level should enable all except silent and quiet.
     *
     * @return void
     */
    public function testItDebugLevelShouldEnableAllExceptSilentAndQuiet(): void
    {
        $this->assertEquals(
            [
                'silent'       => false,
                'quiet'        => false,
                'verbose'      => true,
                'very-verbose' => true,
                'debug'        => true,
            ],
            $this->getVerbosityFlags('php omega test -vvv')
        );
    }
}
