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

use Omega\Application\Application;
use Omega\Console\AbstractCommand;
use Omega\Console\CommandMap;
use Omega\Console\Style\Style;
use Omega\Console\Traits\PrintHelpTrait;
use Omega\Console\Util;
use Omega\Container\Exceptions\BindingResolutionException;
use Omega\Container\Exceptions\EntryNotFoundException;
use Omega\Text\Str;
use Psr\Container\ContainerExceptionInterface;
use ReflectionException;

use function array_merge;
use function class_exists;
use function implode;
use function in_array;
use function method_exists;
use function Omega\Console\info;
use function Omega\Console\style;
use function Omega\Console\warn;
use function ucfirst;

/**
 * Command to display help and command descriptions.
 *
 * This command is used to print help information for registered commands,
 * list available commands, and provide details for specific commands.
 * It supports the following command patterns:
 * - `-h, --help`   : Show help information.
 * - `--list`       : List all registered commands.
 * - `help`         : Get help for a specific command.
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
class HelpCommand extends AbstractCommand
{
    use PrintHelpTrait;

    /**
     * List of namespaces to search for command classes.
     *
     * This array stores namespaces where command classes can be found.
     * It is merged with default namespaces to resolve the correct class names.
     *
     * @var string[]
     */
    protected array $classNamespace = [];

    /**
     * Command registration configuration.
     *
     * Defines the pattern used to invoke the command and the method to execute.
     *
     * @var array<int, array<string, mixed>>
     */
    public static array $command = [
        [
            'pattern' => ['-h', '--help'],
            'fn'      => [self::class, 'main'],
        ], [
            'pattern' => '--list',
            'fn'      => [self::class, 'commandList'],
        ], [
            'pattern' => 'help',
            'fn'      => [self::class, 'commandHelp'],
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
                'help' => 'Get help for available command',
            ],
            'options'   => [],
            'relation'  => [
                'help' => ['[command_name]'],
            ],
        ];
    }

    protected string $banner = '
     _              _ _
 ___| |_ ___    ___| |_|
| . |   | . |  |  _| | |
|  _|_|_|  _|  |___|_|_|
|_|     |_|             ';

    /**
     * Main method to display the help banner and available commands.
     *
     * This method prints the help banner, explains the usage of the command line,
     * and lists all available commands and options for the user.
     *
     * @return int Exit code: always 0.
     * @throws BindingResolutionException Thrown when resolving a binding fails.
     * @throws ContainerExceptionInterface Thrown on general container errors, e.g., service not retrievable.
     * @throws EntryNotFoundException Thrown when no entry exists for the identifier.
     * @throws ReflectionException Thrown when the requested class or interface cannot be reflected.
     */
    public function main(): int
    {
        $hasVisited      = [];

        $this->printHelp = [
            'margin-left'         => 2,
            'column-1-min-length' => 16,
        ];

        foreach ($this->commandMaps() as $command) {
            $class = $command->class();
            if (!in_array($class, $hasVisited)) {
                $hasVisited[] = $class;

                if (class_exists($class)) {
                    $class = new $class([], $command->defaultOption());

                    if (!method_exists($class, 'printHelp')) {
                        continue;
                    }

                    $help = app()->call([$class, 'printHelp']) ?? [];

                    if (isset($help['commands'])) {
                        foreach ($help['commands'] as $command => $desc) {
                            $this->commandDescribes[$command] = $desc;
                        }
                    }

                    if (isset($help['options'])) {
                        foreach ($help['options'] as $option => $desc) {
                            $this->optionDescribes[$option] = $desc;
                        }
                    }

                    if (isset($help['relation']) && $help['relation'] != null) {
                        foreach ($help['relation'] as $option => $desc) {
                            $this->commandRelation[$option] = $desc;
                        }
                    }
                }
            }
        }

        $printer = new Style();
        $printer->push($this->banner)->textYellow();
        $printer
            ->newLines(2)
            ->push('Usage:')->textYellow()
            ->newLines()
            ->repeat(' ', $this->printHelp['margin-left'])
            ->push('php')->textGreen()
            ->push(' omega [flag]')
            ->newLines()
            ->repeat(' ', $this->printHelp['margin-left'])
            ->push('php')->textGreen()
            ->push(' omega [command] ')
            ->push('[option]')->textDim()
            ->newLines(2)

            ->push('Options:')->textYellow()
            ->newLines()
            ->repeat(' ', $this->printHelp['margin-left'])
            ->push('-h, --help')->textDim()->textGreen()
            ->tabs(3)
            ->push('Get all help commands')
            ->newLines()
            ->repeat(' ', $this->printHelp['margin-left'])
            ->push('    --list')->textDim()->textGreen()
            ->tabs(3)
            ->push('Get list of commands registered (class & function)')
            ->newLines(2);

        $printer->push('Available commands:')->textYellow()->newLines();
        $printer = $this->printCommands($printer)->newLines();

        $printer->push('Available options:')->newLines();
        $printer = $this->printOptions($printer);

        $printer->out();

        return 0;
    }

    /**
     * Lists all registered commands with their corresponding class and method names.
     *
     * This method prints a list of all available commands and shows the
     * class and method that define each command's functionality.
     *
     * @return int Exit code: always 0.
     */
    public function commandList(): int
    {
        style('List of all command registered:')->out();

        $mask1    = 0;
        $mask2    = 0;
        $commands = $this->commandMaps();
        foreach ($commands as $command) {
            $option = array_merge($command->cmd(), $command->patterns());
            $length = Str::length(implode(', ', $option));

            if ($length > $mask1) {
                $mask1 = $length;
            }

            $length = Str::length($command->class());
            if ($length > $mask2) {
                $mask2 = $length;
            }
        }

        foreach ($commands as $command) {
            $option = array_merge($command->cmd(), $command->patterns());
            style(implode(', ', $option))->textLightYellow()->out(false);

            $length1 = Str::length(implode(', ', $option));
            $length2 = Str::length($command->class());
            style('')
                ->repeat(' ', $mask1 - $length1 + 4)
                ->push($command->class())->textGreen()
                ->repeat('.', $mask2 - $length2 + 8)->textDim()
                ->push($command->method())
                ->out();
        }

        return 0;
    }

    /**
     * Displays detailed help for a specific command.
     *
     * This method prints help information for a specific command, including its options
     * and relations. If the command is not found, an error message is displayed.
     *
     * @return int Exit code: 0 if help is found, 1 if not.
     * @throws BindingResolutionException Thrown when resolving a binding fails.
     * @throws ContainerExceptionInterface Thrown on general container errors, e.g., service not retrievable.
     * @throws EntryNotFoundException Thrown when no entry exists for the identifier.
     * @throws ReflectionException Thrown when the requested class or interface cannot be reflected.
     */
    public function commandHelp(): int
    {
        if (!isset($this->option[0])) {
            style('')
                ->tap(info('To see help command, place provide command_name'))
                ->textYellow()
                ->push('php omega help <command_name>')->textDim()
                ->newLines()
                ->push('              ^^^^^^^^^^^^')->textRed()
                ->out()
            ;

            return 1;
        }

        $className = $this->option[0];
        if (Str::contains(':', $className)) {
            $className = explode(':', $className);
            $className = $className[0];
        }

        $className .= 'Command';
        $className  = ucfirst($className);
        $namespaces = array_merge(
            $this->classNamespace,
            [
                'App\\Commands\\',
                'Omega\\Console\\Commands\\',
            ]
        );

        foreach ($namespaces as $namespace) {
            $classNames = $namespace . $className;
            if (class_exists($classNames)) {
                $class = new $classNames([]);

                $help = app()->call([$class, 'printHelp']) ?? [];

                if (isset($help['commands']) && $help['commands'] != null) {
                    $this->commandDescribes = $help['commands'];
                }

                if (isset($help['options']) && $help['options'] != null) {
                    $this->optionDescribes = $help['options'];
                }

                if (isset($help['relation']) && $help['relation'] != null) {
                    $this->commandRelation = $help['relation'];
                }

                style('Available command:')->newLines()->out();
                $this->printCommands(new Style())->out();

                style('Available options:')->newLines()->out();
                $this->printOptions(new Style())->out();

                return 0;
            }
        }

        warn("Help for `{$this->option[0]}` command not found")->out(false);

        return 1;
    }

    /**
     * Converts the commands configuration into an array of CommandMap objects.
     *
     * @return CommandMap[] Array of command maps for registered commands.
     */
    private function commandMaps(): array
    {
        return Util::loadCommandFromConfig(Application::getInstance());
    }
}
