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

namespace Tests\Console\Support;

use Omega\Application\Application;
use Omega\Console\AbstractCommand;

use PHPUnit\Framework\Attributes\CoversFunction;
use function Omega\Console\style;

/**
 * A simple console command used for testing command resolution and output.
 *
 * This class is part of the console test fixtures and provides dummy
 * implementations of commands to validate that the console kernel
 * correctly executes commands, handles default options, and interacts
 * with the output style system.
 *
 * Responsibilities:
 * - Print a confirmation message when the command is executed.
 * - Demonstrate handling of default options.
 * - Provide a method with a void return type to test method signatures.
 *
 * This command is used primarily in automated tests to assert
 * the behavior of the console kernel and command dispatching.
 *
 * @category   Tests
 * @package    Console
 * @subpackage Support
 * @link       https://omega-mvc.github.io
 * @author     Adriano Giovannini <agisoftt@gmail.com>
 * @copyright  Copyright (c) 2025 - 2026 Adriano Giovannini
 * @license    https://www.gnu.org/licenses/gpl-3.0-standalone.html GPL V3.0+
 * @version    2.0.0
 */
#[CoversFunction('Omega\Console\style')]
class FoundedCommand extends AbstractCommand
{
    /**
     * Main entry point of the command.
     *
     * Outputs a message indicating that the command has been executed successfully.
     *
     * @return int Exit status code (0 indicates success)
     */
    public function main(): int
    {
        style('command has founded')->out();

        return 0;
    }

    /**
     * Default method called when no specific mode or option is provided.
     *
     * Prints the default property value of the command using the style helper.
     *
     * @return int Exit status code (0 indicates success)
     */
    public function default(): int
    {
        style($this->default)->out(false);

        return 0;
    }

    /**
     * A method with void return type used for testing purposes.
     *
     * Accepts an Application instance as a parameter but does not return
     * any value. Useful for verifying that commands can define methods
     * that do not require a return.
     *
     * @param Application $app The application instance, typically used
     *                         for accessing services, configuration, or
     *                         container-bound dependencies.
     * @return void
     */
    public function returnVoid(Application $app): void
    {
    }
}
