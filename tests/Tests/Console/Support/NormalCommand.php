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
use Omega\Console\CommandMap;
use Omega\Console\Console;
use PHPUnit\Framework\Attributes\CoversNothing;

/**
 * A console test kernel used to simulate various command scenarios for Omega.
 *
 * This class extends the main `Console` kernel and overrides the `commands()` method
 * to provide a set of predefined test commands. It is designed for use in the console
 * test suite to validate command resolution, pattern matching, group commands, and
 * default option handling.
 *
 * Commands provided by this kernel include:
 * - Normal commands returning integer exit codes.
 * - Commands returning void for edge cases.
 * - Commands using patterns, match callbacks, and default options.
 *
 * @category  Omega
 * @package   Console\TestFixtures
 * @author    Adriano Giovannini <agisoftt@gmail.com>
 * @copyright Copyright (c) 2025 - 2026 Adriano Giovannini
 * @license   https://www.gnu.org/licenses/gpl-3.0-standalone.html GPL V3.0+
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
#[CoversNothing]
class NormalCommand extends Console
{
    /**
     * Create a new NormalCommand instance.
     *
     * Simply calls the parent Console constructor with the application container.
     *
     * @param Application $app The application container instance.
     */
    public function __construct(Application $app)
    {
        parent::__construct($app);
    }

    /**
     * Return the set of commands available in this test kernel.
     *
     * Each entry is an instance of `CommandMap`, which defines:
     * - `cmd`: the command string or array of strings.
     * - `mode`: optional mode of the command.
     * - `class`: the command class to execute.
     * - `fn`: the method or callback to call on the command class.
     * - `default`: optional default parameters.
     * - `match`: optional callback to match dynamic commands.
     * - `pattern`: optional pattern string or array for pattern-based commands.
     *
     * @return CommandMap[] An array of CommandMap objects defining all commands.
     */
    protected function commands(): array
    {
        return [
            // old style
            new CommandMap([
                'cmd'     => 'use:full',
                'mode'    => 'full',
                'class'   => FoundedCommand::class,
                'fn'      => 'main',
            ]),
            new CommandMap([
                'cmd'     => ['use:group', 'group'],
                'class'   => FoundedCommand::class,
                'fn'      => 'main',
            ]),
            new CommandMap([
                'cmd'     => 'start:',
                'mode'    => 'start',
                'class'   => FoundedCommand::class,
                'fn'      => 'main',
            ]),
            new CommandMap([
                'cmd'     => 'use:without_mode',
                'class'   => FoundedCommand::class,
                'fn'      => 'main',
            ]),
            new CommandMap([
                'cmd'     => 'use:without_main',
                'class'   => FoundedCommand::class,
            ]),
            new CommandMap([
                'match'   => fn ($given) => $given == 'use:match',
                'fn'      => [FoundedCommand::class, 'main'],
            ]),
            new CommandMap([
                'pattern' => 'use:pattern',
                'fn'      => [FoundedCommand::class, 'main'],
            ]),
            new CommandMap([
                'pattern' => ['pattern1', 'pattern2'],
                'fn'      => [FoundedCommand::class, 'main'],
            ]),
            new CommandMap([
                'pattern' => 'use:default_option',
                'fn'      => [FoundedCommand::class, 'default'],
                'default' => [
                    'default' => 'test',
                ],
            ]),
            new CommandMap([
                'pattern' => 'use:no-int-return',
                'fn'      => [FoundedCommand::class, 'returnVoid'],
            ]),
        ];
    }
}
