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

namespace Omega\Console\Commands\Make;

use Omega\Console\Commands\MakeCommand;
use Omega\Console\Style\Style;
use Omega\Container\Exceptions\BindingResolutionException;
use Omega\Container\Exceptions\CircularAliasException;
use Omega\Container\Exceptions\EntryNotFoundException;
use Psr\Container\ContainerExceptionInterface;
use ReflectionException;

use function dirname;
use function file_get_contents;
use function file_put_contents;
use function Omega\Console\error;
use function Omega\Console\info;
use function Omega\Console\success;
use function Omega\Support\get_path;
use function Omega\Support\path;
use function Omega\Support\slash;
use function str_replace;

/**
 * Handles the creation of new command classes within the application.
 *
 * This command automates the scaffolding of a fully functional CLI command
 * by copying a predefined stub, replacing placeholders with the specified
 * command name, and updating the application's command registry. It ensures
 * that new command classes are correctly structured and immediately
 * available for use in the CLI environment.
 *
 * @category   Omega
 * @package    Console
 * @subpackage Commands\Make
 * @link       https://omega-mvc.github.io
 * @author     Adriano Giovannini <agisoftt@gmail.com>
 * @copyright  Copyright (c) 2025 - 2026 Adriano Giovannini
 * @license    https://www.gnu.org/licenses/gpl-3.0-standalone.html  GPL V3.0+
 * @version    2.0.0
 */
final class MakeCommandCommand extends MakeCommand
{
    /**
     * Generates a new command class.
     *
     * @return int Exit code: 0 on success, 1 on failure
     * @throws BindingResolutionException Thrown when resolving a binding fails.
     * @throws CircularAliasException Thrown when alias resolution loops recursively.
     * @throws ContainerExceptionInterface Thrown on general container errors, e.g., service not retrievable.
     * @throws EntryNotFoundException Thrown when no entry exists for the identifier.
     * @throws ReflectionException Thrown when the requested class or interface cannot be reflected.
     */
    public function makeCommand(): int
    {
        info('Making command file...')->out(false);

        $this->isPath('path.command');

        $name    = $this->option[0];
        $success = $this->makeTemplate($name, [
            'template_location' => slash(path: dirname(__DIR__) . '/stubs/command'),
            'save_location'     => get_path('path.command'),
            'pattern'           => '__command__',
            'suffix'            => 'Command.php',
        ]);

        if ($success) {
            $getContent = file_get_contents(get_path('path.config') . 'command.php');
            /** @noinspection PhpUnnecessaryCurlyVarSyntaxInspection */
            $getContent = str_replace(
                '// more command here',
                "// {$name} \n\t" . 'App\\Commands\\' . $name . 'Command::$' . "command\n\t// more command here",
                $getContent
            );

            file_put_contents(get_path('path.config') . 'command.php', $getContent);

            $path = path('app.Console.Commands') . $name . 'Command.php';

            success('Command [' . new Style($path)->bold() . '] create successfully.')->out();

            return 0;
        }

        error("\nFailed Create command file")->out();

        return 1;
    }
}
