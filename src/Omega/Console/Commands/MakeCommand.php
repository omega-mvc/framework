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
use Omega\Console\Commands\Make\MakeCommandCommand;
use Omega\Console\Commands\Make\MakeControllerCommand;
use Omega\Console\Commands\Make\MakeExceptionCommand;
use Omega\Console\Commands\Make\MakeMiddlewareCommand;
use Omega\Console\Commands\Make\MakeMigrationCommand;
use Omega\Console\Commands\Make\MakeModelCommand;
use Omega\Console\Commands\Make\MakeProviderCommand;
use Omega\Console\Commands\Make\MakeViewCommand;
use Omega\Console\Traits\CommandTrait;

use function file_exists;
use function file_get_contents;
use function file_put_contents;
use function is_dir;
use function mkdir;
use function Omega\Console\warn;
use function preg_replace;
use function str_replace;
use function ucfirst;

/**
 * Base command class for generating application resources.
 *
 * Serves as the central orchestrator for generating controllers, models,
 * migrations, views, middleware, providers, exceptions, and commands.
 * Concrete make commands extend this class and implement the `MakeableCreate`
 * interface to provide a unified creation method.
 */
/**
 * @category   Omega
 * @package    Console
 * @subpackage Commands
 * @link       https://omega-mvc.github.io
 * @author     Adriano Giovannini <agisoftt@gmail.com>
 * @copyright  Copyright (c) 2025 - 2026 Adriano Giovannini (https://omega-mvc.github.io)
 * @license    https://www.gnu.org/licenses/gpl-3.0-standalone.html     GPL V3.0+
 * @version    2.0.0
 *
 * @property bool $update
 * @property bool $force
 */
class MakeCommand extends AbstractCommand
{
    use CommandTrait;

    /**
     * Command registration configuration.
     *
     * Defines the pattern used to invoke the command and the method to execute.
     *
     * @var array<int, array<string, mixed>>
     */
    public static array $command = [
        [
            'pattern' => 'make:command',
            'fn'      => [MakeCommandCommand::class, 'makeCommand'],
        ], [
            'pattern' => 'make:controller',
            'fn'      => [MakeControllerCommand::class, 'makeController'],
        ], [
            'pattern' => 'make:exception',
            'fn'      => [MakeExceptionCommand::class, 'makeException'],
        ], [
            'pattern' => 'make:middleware',
            'fn'      => [MakeMiddlewareCommand::class, 'makeMiddleware'],
        ], [
            'pattern' => 'make:migration',
            'fn'      => [MakeMigrationCommand::class, 'makeMigration'],
        ], [
            'pattern' => 'make:model',
            'fn'      => [MakeModelCommand::class, 'makeModel'],
        ], [
            'pattern' => 'make:provider',
            'fn'      => [MakeProviderCommand::class, 'makeProvider'],
        ], [
            'pattern' => 'make:view',
            'fn'      => [MakeViewCommand::class, 'makeView'],
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
                'make:command'    => 'Generate new command class',
                'make:controller' => 'Generate new controller class',
                'make:exception'  => 'Generate new exception class',
                'make:middleware' => 'Generate new middleware class',
                'make:migration'  => 'Generate new migration file',
                'make:model'      => 'Generate new model class',
                'make:provider'   => 'Generate new service provider class',
                'make:view'       => 'Generate new view template',
            ],
            'options'   => [
                '--table-name'    => 'Set table column when creating model.',
                '--update'        => 'Generate migration file with alter (update).',
                '--force'         => 'Force to creating template.',
            ],
            'relation'  => [
                'make:command'    => ['[command_name]'],
                'make:controller' => ['[controller_name]'],
                'make:exception'  => ['[exception_name]'],
                'make:middleware' => ['[middleware_name]'],
                'make:migration'  => ['[table_name]', '--update'],
                'make:model'      => ['[model_name]', '--table-name', '--force'],
                'make:provider'   => ['[provider_name]'],
                'make:view'       => ['[view_name]'],
            ],
        ];
    }

    /**
     *
     * @param string $argument Name of the class/file to generate
     * @param array<string, string> $makeOption Configuration for template replacement
     * @param string $folder Optional folder to create/save the file
     * @return bool True if the template was successfully copied, false otherwise
     */
    protected function makeTemplate(string $argument, array $makeOption, string $folder = ''): bool
    {
        $folder = ucfirst($folder);
        if (file_exists($fileName = $makeOption['save_location'] . $folder . $argument . $makeOption['suffix'])) {
            warn('File already exist')->out(false);

            return false;
        }

        if ('' !== $folder && !is_dir($makeOption['save_location'] . $folder)) {
            mkdir($makeOption['save_location'] . $folder);
        }

        $getTemplate = file_get_contents($makeOption['template_location']);
        $getTemplate = str_replace($makeOption['pattern'], ucfirst($argument), $getTemplate);
        $getTemplate = preg_replace('/^.+\n/', '', $getTemplate);
        $isCopied    = file_put_contents($fileName, $getTemplate);

        return !($isCopied === false);
    }
}
