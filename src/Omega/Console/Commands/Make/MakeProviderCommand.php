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
 * Generates a new service provider class.
 *
 * This command creates a PHP service provider file that extends
 * the base ServiceProvider class. It sets up a ready-to-use
 * provider in the application's service container, allowing
 * developers to register services, bindings, or configurations
 * in a structured and consistent way.
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
final class MakeProviderCommand extends MakeCommand
{
    /**
     * Generates a new service provider class.
     *
     * @return int Exit code: 0 on success, 1 on failure
     * @throws BindingResolutionException Thrown when resolving a binding fails.
     * @throws CircularAliasException Thrown when alias resolution loops recursively.
     * @throws ContainerExceptionInterface Thrown on general container errors, e.g., service not retrievable.
     * @throws EntryNotFoundException Thrown when no entry exists for the identifier.
     * @throws ReflectionException Thrown when the requested class or interface cannot be reflected.
     */
    public function makeProvider(): int
    {
        info('Making service provider file...')->out(false);

        $this->isPath('path.provider');

        $success = $this->makeTemplate($this->option[0], [
            'template_location' => slash(path: dirname(__DIR__) . '/stubs/provider'),
            'save_location'     => get_path('path.provider'),
            'pattern'           => '__provider__',
            'suffix'            => 'ServiceProvider.php',
        ]);

        $path = path('app.Http.Providers') . $this->option[0] . 'Provider.php';

        if ($success) {
            success('Provider [' . new Style($path)->bold() . '] created successfully.')->out();

            return 0;
        }

        error('Failed Create services file')->out();

        return 1;
    }
}


