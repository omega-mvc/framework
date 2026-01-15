<?php

/** @noinspection PhpUnnecessaryCurlyVarSyntaxInspection */

declare(strict_types=1);

namespace Tests\Console;

use PHPUnit\Framework\TestCase;
use Omega\Console\Traits\TerminalTrait;

use function fopen;
use function getenv;
use function tmpfile;

use const STDOUT;

class HasColorSupportTest extends TestCase
{
    use EnvironmentIsolationTrait;

    private $testClass;
    private bool $isCI;

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
        parent::setUp();

        $this->backupEnvironment();
        $this->isCI = getenv('CI') !== false || getenv('GITHUB_ACTIONS') === 'true';

        $this->testClass = new class {
            use TerminalTrait;

            public function color($stream = STDOUT): bool
            {
                return $this->hasColorSupport($stream);
            }
        };
    }

    public function testNoColorOverridesEverything(): void
    {
        $this->clearEnvironment();

        $this->setTestEnvironments([
            'NO_COLOR'     => '1',
            'TERM_PROGRAM' => 'Hyper',
            'COLORTERM'    => 'truecolor',
            'TERM'         => 'xterm-256color',
        ]);

        $result = $this->testClass->color();
        $this->assertFalse($result, 'NO_COLOR should override all other color-enabling settings');
    }

    public function testColorSupportedTerminals(): void
    {
        if ($this->isCI) {
            $this->markTestSkipped('CI environment does not support color programs');
        }

        $supportedTerminals = [
            'xterm'          => true,
            'xterm-256color' => true,
            'screen'         => true,
            'tmux-256color'  => true,
            'linux'          => true,
            // 'dumb' => false,     // WARNING: windows (local) does not support this
            // 'unknown' => false,  // WARNING: windows (local) does not support this
        ];

        foreach ($supportedTerminals as $term => $expectedSupport) {
            $this->clearEnvironment();
            $this->setTestEnvironment('TERM', $term);

            $result = $this->testClass->color();
            $this->assertEquals(
                $expectedSupport,
                $result,
                "TERM={$term} should " . ($expectedSupport ? 'support' : 'not support') . ' colors'
            );
        }
    }

    public function testColorEnabledBySpecialPrograms(): void
    {
        if ($this->isCI) {
            $this->markTestSkipped('CI environment does not support color programs');
        }

        $colorPrograms = [
            'TERM_PROGRAM' => 'Hyper',
            'COLORTERM'    => 'truecolor',
            'ANSICON'      => '1',
            'ConEmuANSI'   => 'ON',
        ];

        foreach ($colorPrograms as $envVar => $value) {
            $this->clearEnvironment();
            $this->setTestEnvironments([
                $envVar => $value,
                'TERM'  => 'unknown',
            ]);

            $result = $this->testClass->color();
            $this->assertTrue($result, "{$envVar}={$value} should enable color support");
        }
    }

    public function testSystemSupport(): void
    {
        $systems = ['MINGW32', 'MINGW64', 'UCRT64', 'CLANG64'];

        foreach ($systems as $system) {
            $this->clearEnvironment();
            $this->setTestEnvironment('MSYSTEM', $system);

            $result = $this->testClass->color();
            $this->assertIsBool($result, "MSYSTEM={$system} should return boolean");
        }
    }

    public function testWithCustomStreams(): void
    {
        $this->clearEnvironment();

        $streams = [
            'memory' => fopen('php://memory', 'w+'),
            'temp'   => tmpfile(),
            'stdout' => STDOUT,
        ];

        foreach ($streams as $type => $stream) {
            if ($stream !== false) {
                $result = $this->testClass->color($stream);
                $this->assertIsBool($result, "Should handle {$type} stream gracefully");

                if ($stream !== STDOUT) {
                    fclose($stream);
                }
            }
        }
    }

    /**
     * Tears down the environment after each test method.
     *
     * This method is called automatically by PHPUnit after each test runs.
     * It is responsible for cleaning up resources, flushing the application
     * state, unsetting properties, and resetting any static or global state
     * to avoid side effects between tests.
     *
     * @return void
     */
    protected function tearDown(): void
    {
        $this->restoreEnvironment();
        parent::tearDown();
    }
}
