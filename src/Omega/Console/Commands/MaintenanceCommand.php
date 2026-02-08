<?php

/**
 * Part of Omega - Console Package.
 *
 * @link      https://omega-mvc.github.io
 * @author    Adriano Giovannini <agisoftt@gmail.com>
 * @copyright Copyright (c) 2025 - 2026 Adriano Giovannini (https://omega-mvc.github.io)
 * @license   https://www.gnu.org/licenses/gpl-3.0-standalone.html     GPL V3.0+
 * @version   2.0.0
 */

declare(strict_types=1);

namespace Omega\Console\Commands;

use Omega\Console\AbstractCommand;
use Omega\Console\Traits\PrintHelpTrait;
use Omega\Container\Exceptions\BindingResolutionException;
use Omega\Container\Exceptions\CircularAliasException;
use Omega\Container\Exceptions\EntryNotFoundException;
use Psr\Container\ContainerExceptionInterface;
use ReflectionException;

use function file_exists;
use function file_get_contents;
use function file_put_contents;
use function Omega\Console\info;
use function Omega\Console\success;
use function Omega\Console\warn;

/**
 * Command to toggle application maintenance mode.
 *
 * Provides commands to activate maintenance mode (`down`) and
 * deactivate it (`up`). When in maintenance mode, the application
 * can display a maintenance page or block user access.
 *
 * @category   Omega
 * @package    Console
 * @subpackage Commands
 * @link       https://omega-mvc.github.io
 * @author     Adriano Giovannini <agisoftt@gmail.com>
 * @copyright  Copyright (c) 2025 - 2026 Adriano Giovannini (https://omega-mvc.github.io)
 * @license    https://www.gnu.org/licenses/gpl-3.0-standalone.html     GPL V3.0+
 * @version    2.0.0
 */
class MaintenanceCommand extends AbstractCommand
{
    use PrintHelpTrait;

    /**
     * Command registration configuration.
     *
     * Defines the pattern used to invoke the command and the method to execute.
     *
     * @var array<int, array<string, mixed>>
     */
    public static array $command = [
        [
            'pattern' => 'up',
            'fn'      => [self::class, 'up'],
        ], [
            'pattern' => 'down',
            'fn'      => [self::class, 'down'],
        ],
    ];

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
                'down' => 'Active maintenance mode',
                'up'   => 'Deactivate maintenance mode',
            ],
            'options'   => [],
            'relation'  => [],
        ];
    }

    /**
     * Activates maintenance mode for the application.
     *
     * If the application is already in maintenance mode, a warning is displayed.
     * Otherwise, necessary maintenance files are created to signal maintenance state.
     *
     * @return int Exit code: 0 on success, 1 if already in maintenance mode
     * @throws BindingResolutionException Thrown when resolving a binding fails.
     * @throws CircularAliasException Thrown when alias resolution loops recursively.
     * @throws ContainerExceptionInterface Thrown on general container errors, e.g., service not retrievable.
     * @throws EntryNotFoundException Thrown when no entry exists for the identifier.
     * @throws ReflectionException Thrown when the requested class or interface cannot be reflected.
     */
    public function down(): int
    {
        if (app()->isDownMaintenanceMode()) {
            warn('Application is already under maintenance mode.')->out();

            return 1;
        }

        if (false === file_exists($down = get_path('path.storage') . 'app/down')) {
            file_put_contents(
                $down,
                file_get_contents(slash(path: __DIR__ . '/stubs/down'))
            );
        }

        file_put_contents(
            get_path('path.storage') . 'app/maintenance.php',
            file_get_contents(slash(path: __DIR__ . '/stubs/maintenance'))
        );
        success('Successfully, your application now in under maintenance.')->out();

        return 0;
    }

    /**
     * Deactivates maintenance mode for the application.
     *
     * If the application is not in maintenance mode, a warning is displayed.
     * Otherwise, the maintenance file is removed, allowing the application to resume normal operation.
     *
     * @return int Exit code: 0 on success, 1 if not in maintenance mode or removal failed
     * @throws BindingResolutionException Thrown when resolving a binding fails.
     * @throws CircularAliasException Thrown when alias resolution loops recursively.
     * @throws ContainerExceptionInterface Thrown on general container errors, e.g., service not retrievable.
     * @throws EntryNotFoundException Thrown when no entry exists for the identifier.
     * @throws ReflectionException Thrown when the requested class or interface cannot be reflected.
     */
    public function up(): int
    {
        if (false === app()->isDownMaintenanceMode()) {
            warn('Application is not in maintenance mode.')->out();

            return 1;
        }

        if (false === unlink($up = get_path('path.storage') . 'app/maintenance.php')) {
            warn('Application stil maintenance mode.')->out(false);
            info("Remove manually maintenance file in `$up`.")->out();

            return 1;
        }

        success('Successfully, your application now live.')->out();

        return 0;
    }
}
