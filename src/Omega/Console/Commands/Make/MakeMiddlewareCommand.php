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
 * Handles the creation of a new middleware class within the application.
 *
 * This command generates a middleware file using a predefined stub,
 * ensuring consistent structure, namespace, and naming conventions.
 * The generated middleware is placed in the designated middleware directory,
 * ready for immediate registration and use in the application's request pipeline.
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
final class MakeMiddlewareCommand extends MakeCommand
{
    /**
     * Generates a new middleware class.
     *
     * @return int Exit code: 0 on success, 1 on failure
     * @throws BindingResolutionException Thrown when resolving a binding fails.
     * @throws CircularAliasException Thrown when alias resolution loops recursively.
     * @throws ContainerExceptionInterface Thrown on general container errors, e.g., service not retrievable.
     * @throws EntryNotFoundException Thrown when no entry exists for the identifier.
     * @throws ReflectionException Thrown when the requested class or interface cannot be reflected.
     */
    public function makeMiddleware(): int
    {
        info('Making middleware file...')->out(false);

        $this->isPath('path.middleware');

        $success = $this->makeTemplate($this->option[0], [
            'template_location' => slash(path: dirname(__DIR__) . '/stubs/middleware'),
            'save_location'     => get_path('path.middleware'),
            'pattern'           => '__middleware__',
            'suffix'            => 'Middleware.php',
        ]);

        $path = path('app.Http.Middlewares') . $this->option[0] . 'Middleware.php';

        if ($success) {
            success('Middleware [' . new Style($path)->bold() . '] created successfully.')->out();

            return 0;
        }

        error('Failed create middleware.')->out();

        return 1;
    }
}
