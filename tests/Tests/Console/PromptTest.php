<?php

declare(strict_types=1);

namespace Tests\Console;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Omega\Text\Str;

use function fclose;
use function function_exists;
use function fwrite;
use function proc_close;
use function proc_open;
use function stream_get_contents;

#[CoversClass(Str::class)]
final class PromptTest extends TestCase
{
    private function runCommand($command, $input): false|string
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
        $cli    = slash(path: __DIR__ . '/fixtures/option');
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
        $cli    = slash(path: __DIR__ . '/fixtures/option');
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
        $cli    = slash(path: __DIR__ . '/fixtures/select');
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
        $cli    = slash(path: __DIR__ . '/fixtures/select');
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
        $cli    = slash(path: __DIR__ . '/fixtures/text');
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
        $cli    = slash(path: __DIR__ . '/fixtures/any');
        $output = $this->runCommand('php "' . $cli . '"', $input);

        $this->assertTrue(Str::contains($output, 'you press f'));
    }
}
