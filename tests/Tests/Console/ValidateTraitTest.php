<?php

declare(strict_types=1);

namespace Tests\Console;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Omega\Console\AbstractCommand;
use Omega\Console\Style\Style;
use Omega\Console\Traits\ValidateCommandTrait;
use Omega\Text\Str;
use Omega\Validator\Rule\ValidPool;

use function ob_get_clean;
use function ob_start;

#[CoversClass(AbstractCommand::class)]
#[CoversClass(Style::class)]
#[CoversClass(Str::class)]
final class ValidateTraitTest extends TestCase
{
    private $command;

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
        $this->command = new class (['php', 'omega', '--test', 'oke']) extends AbstractCommand {
            use ValidateCommandTrait;

            public function main(): void
            {
                $this->initValidate($this->optionMapper);
                $this->getValidateMessage(new Style())->out(false);
            }

            protected function validateRule(ValidPool $rules): void
            {
                $rules('test')->required()->min_len(5);
            }
        };
    }

    /**
     * Test it can make text red.
     *
     * @return void
     */
    public function testItCanMakeTextRed(): void
    {
        ob_start();
        $this->command->main();
        $out = ob_get_clean();

        $this->assertTrue(Str::contains($out, 'The Test field needs to be at least 5 characters'));
    }
}
