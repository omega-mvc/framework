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

namespace Tests\Console\Commands;

use Omega\Console\AbstractCommand;
use PHPUnit\Framework\Attributes\CoversClass;

/**
 * Class RegisterHelpCommand
 *
 * A test command used to register entries for the console help system.
 * This command provides a predefined set of commands, options, and their relations
 * to simulate a real command registration scenario. It is primarily used in tests
 * to verify that the help command correctly displays information for dynamically
 * registered commands and their associated options.
 *
 * @category   Tests
 * @package    Console
 * @subpackage Commands
 * @link       https://omega-mvc.github.io
 * @author     Adriano Giovannini <agisoftt@gmail.com>
 * @copyright  Copyright (c) 2025 - 2026 Adriano Giovannini
 * @license    https://www.gnu.org/licenses/gpl-3.0-standalone.html GPL V3.0+
 * @version    2.0.0
 */
#[CoversClass(AbstractCommand::class)]
final class RegisterHelpCommand extends AbstractCommand
{
    /**
     * Returns a description of the command, its options, and their relations.
     *
     * This is used to generate help output for users.
     *
     * @return array<string, array<string, string|string[]>>
     */
    public function printHelp(): array
    {
        return [
            'commands'  => [
                'test' => 'some test will appear in test',
            ],
            'options'   => [
                '--test' => 'this also will display in test',
            ],
            'relation'  => [
                'test' => ['[unit]'],
            ],
        ];
    }
}
