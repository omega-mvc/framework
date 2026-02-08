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

use Omega\Console\AbstractCommand;
use Omega\Console\Style\Style;
use Omega\Console\Traits\ValidateCommandTrait;
use Omega\Text\Str;
use Omega\Validator\Rule\ValidPool;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversTrait;
use PHPUnit\Framework\TestCase;

use function ob_get_clean;
use function ob_start;

/**
 * Class ValidateTraitTest
 *
 * This test class is designed to verify the behavior of the
 * ValidateCommandTrait when used in a command context. It uses
 * an anonymous class extending AbstractCommand to expose and
 * invoke trait methods, ensuring validation rules are applied
 * correctly and validation messages are generated as expected.
 *
 * The tests focus on:
 *  - Initializing the validation system with the command's
 *    option mapper.
 *  - Applying validation rules to fields.
 *  - Checking that appropriate validation messages are
 *    output for invalid input.
 *
 * @category  Tests
 * @package   Console
 * @link      https://omega-mvc.github.io
 * @author    Adriano Giovannini <agisoftt@gmail.com>
 * @copyright Copyright (c) 2025 - 2026 Adriano Giovannini (https://omega-mvc.github.io)
 * @license   https://www.gnu.org/licenses/gpl-3.0-standalone.html     GPL V3.0+
 * @version   2.0.0
 */
#[CoversClass(AbstractCommand::class)]
#[CoversClass(Style::class)]
#[CoversClass(Str::class)]
#[CoversTrait(ValidateCommandTrait::class)]
final class ValidateTraitTest extends TestCase
{
    /**
     * Command instance used to test CommandTrait behavior.
     *
     * This is an anonymous class extending AbstractCommand and
     * using CommandTrait, created specifically to expose and
     * invoke trait methods during testing.
     *
     * @var AbstractCommand
     */
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
