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
use Omega\Container\Exceptions\BindingResolutionException;
use Omega\Container\Exceptions\CircularAliasException;
use Omega\Container\Exceptions\EntryNotFoundException;
use Psr\Container\ContainerExceptionInterface;
use ReflectionException;

use function dirname;
use function Omega\Console\error;
use function Omega\Console\info;
use function Omega\Console\success;
use function Omega\Support\get_path;
use function Omega\Support\slash;

/**
 * Generates a new view template.
 *
 * This command creates a PHP view file based on a predefined stub.
 * It sets up a ready-to-use template in the application's view
 * directory, providing a starting point for rendering HTML
 * content with dynamic data in a structured and consistent way.
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
final class MakeViewCommand extends MakeCommand
{
    /**
     * Generates a new view template.
     *
     * @return int Exit code: 0 on success, 1 on failure
     * @throws BindingResolutionException Thrown when resolving a binding fails.
     * @throws CircularAliasException Thrown when alias resolution loops recursively.
     * @throws ContainerExceptionInterface Thrown on general container errors, e.g., service not retrievable.
     * @throws EntryNotFoundException Thrown when no entry exists for the identifier.
     * @throws ReflectionException Thrown when the requested class or interface cannot be reflected.
     */
    public function makeView(): int
    {
        info('Making view file...')->out(false);

        $success = $this->makeTemplate($this->option[0], [
            'template_location' => slash(path: dirname(__DIR__) . '/stubs/view'),
            'save_location'     => get_path('path.view'),
            'pattern'           => '__view__',
            'suffix'            => '.template.php',
        ]);

        if ($success) {
            success('Finish created view file')->out();

            return 0;
        }

        error('Failed Create view file')->out();

        return 1;
    }
}
