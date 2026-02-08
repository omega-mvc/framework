<?php

/**
 * Part of Omega - Tests\Console Package.
 *
 * @link      https://omega-mvc.github.io
 * @author    Adriano Giovannini <agisoftt@gmail.com>
 * @copyright Copyright (c) 2025 - 2026 Adriano Giovannini (https://omega-mvc.github.io)
 * @license   https://www.gnu.org/licenses/gpl-3.0-standalone.html     GPL V3.0+
 * @version   2.0.0
 */

declare(strict_types=1);

namespace Tests\Console;

use Omega\Text\Str;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

use function fclose;
use function function_exists;
use function fwrite;
use function proc_close;
use function proc_open;
use function stream_get_contents;

/**
 * Integration tests for interactive console prompts.
 *
 * This test suite executes real PHP CLI scripts as separate processes
 * and simulates user input via STDIN in order to verify prompt behavior.
 *
 * Covered prompts include:
 * - option prompts
 * - select prompts
 * - text input prompts
 * - any-key prompts
 *
 * The tests assert the actual CLI output, ensuring the prompt system
 * behaves correctly when running in a real terminal-like environment.
 *
 * @category  Tests
 * @package   Console
 * @link      https://omega-mvc.github.io
 * @author    Adriano Giovannini <agisoftt@gmail.com>
 * @copyright Copyright (c) 2025 - 2026 Adriano Giovannini (https://omega-mvc.github.io)
 * @license   https://www.gnu.org/licenses/gpl-3.0-standalone.html     GPL V3.0+
 * @version   2.0.0
 */
#[CoversClass(Str::class)]
final class PromptTest extends TestCase
{
    /**
     * Execute a CLI command and simulate user input via STDIN.
     *
     * The command is executed as a separate process using proc_open(),
     * allowing the test to write to STDIN and capture both STDOUT and STDERR.
     *
     * This is used to test interactive console commands that rely on
     * user input, such as prompts and selections.
     *
     * @param string $command The full command to execute (e.g. "php script.php")
     * @param string $input   The input to be written to STDIN
     *
     * @return string|false The captured STDOUT output, or false on failure
     */
    private function runCommand(string $command, string $input): false|string
    {
        $descriptors = [
            0 => ['pipe', 'r'], // input
            1 => ['pipe', 'w'], // output
            2 => ['pipe', 'w'], // errors
        ];

        $process = proc_open($command, $descriptors, $pipes);

        fwrite($pipes[0], $input);
        fclose($pipes[0]);

        $output = stream_get_contents($pipes[1]);
        fclose($pipes[1]);

        $errors = stream_get_contents($pipes[2]);
        fclose($pipes[2]);

        proc_close($process);

        return $output;
    }

    /**
     * Test option prompt.
     *
     * @return void
     */
    public function testOptionPrompt(): void
    {
        $input  = 'test_1';
        $cli    = slash(path: __DIR__ . '/Support/option');
        $output = $this->runCommand('php "' . $cli . '"', $input);

        $this->assertTrue(Str::contains($output, 'ok'));
    }

    /**
     * Test option prompt default.
     *
     * @return void
     */
    public function testOptionPromptDefault(): void
    {
        $input  = 'test_2';
        $cli    = slash(path: __DIR__ . '/Support/option');
        $output = $this->runCommand('php "' . $cli . '"', $input);

        $this->assertTrue(Str::contains($output, 'default'));
    }

    /**
     * Test select prompt.
     *
     * @return void
     */
    public function testSelectPrompt(): void
    {
        $input  = '1';
        $cli    = slash(path: __DIR__ . '/Support/select');
        $output = $this->runCommand('php "' . $cli . '"', $input);

        $this->assertTrue(Str::contains($output, 'ok'));
    }

    /**
     * Test select prompt default.
     *
     * @return void
     */
    public function testSelectPromptDefault(): void
    {
        $input  = 'rz';
        $cli    = slash(path: __DIR__ . '/Support/select');
        $output = $this->runCommand('php "' . $cli . '"', $input);

        $this->assertTrue(Str::contains($output, 'default'));
    }

    /**
     * Test text prompt.
     *
     * @return void
     */
    public function testTextPrompt(): void
    {
        $input  = 'text';
        $cli    = slash(path: __DIR__ . '/Support/text');
        $output = $this->runCommand('php "' . $cli . '"', $input);

        $this->assertTrue(Str::contains($output, 'text'));
    }

    /**
     * Test any key prompt.
     *
     * @return void
     */
    public function testAnyKeyPrompt(): void
    {
        if (!function_exists('readline_callback_handler_install')) {
            $this->markTestSkipped("Console doest support 'readline_callback_handler_install'");
        }

        $input  = 'f';
        $cli    = slash(path: __DIR__ . '/Support/any');
        $output = $this->runCommand('php "' . $cli . '"', $input);

        $this->assertTrue(Str::contains($output, 'you press f'));
    }
}
