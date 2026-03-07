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
use function Omega\Console\error;
use function Omega\Console\info;
use function Omega\Console\success;

/**
 * Handles the creation of a new controller class within the application.
 *
 * This command generates a fully scaffolded controller file based on a predefined stub,
 * placing it in the appropriate application directory. It ensures consistency in naming,
 * namespace, and structure for all controllers created through the CLI.
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
final class MakeControllerCommand extends MakeCommand
{
    /**
     * Generates a new controller class.
     *
     * @return int Exit code: 0 on success, 1 on failure
     * @throws BindingResolutionException Thrown when resolving a binding fails.
     * @throws CircularAliasException Thrown when alias resolution loops recursively.
     * @throws ContainerExceptionInterface Thrown on general container errors, e.g., service not retrievable.
     * @throws EntryNotFoundException Thrown when no entry exists for the identifier.
     * @throws ReflectionException Thrown when the requested class or interface cannot be reflected.
     */
    public function makeController(): int
    {
        info('Making controller file...')->out(false);

        $this->isPath('path.controller');

        $success = $this->makeTemplate($this->option[0], [
            'template_location' => slash(path: dirname(__DIR__) . '/stubs/controller'),
            'save_location'     => get_path('path.controller'),
            'pattern'           => '__controller__',
            'suffix'            => 'Controller.php',
        ]);

        $path = path('app.Http.Controllers') . $this->option[0] . 'Controller.php';

        if ($success) {
            success('Controller [' . new Style($path)->bold() . '] created successfully.')->out();

            return 0;
        }

        error('Failed Create controller')->out();

        return 1;
    }
}
