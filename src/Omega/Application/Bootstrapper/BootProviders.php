<?php

/**
 * Part of Omega - Application Package.
 *
 * @link      https://omega-mvc.github.io
 * @author    Adriano Giovannini <agisoftt@gmail.com>
 * @copyright Copyright (c) 2025 - 2026 Adriano Giovannini (https://omega-mvc.github.io)
 * @license   https://www.gnu.org/licenses/gpl-3.0-standalone.html     GPL V3.0+
 * @version   2.0.0
 */

declare(strict_types=1);

namespace Omega\Application\Bootstrapper;

use Omega\Application\ApplicationInterface;
use Omega\Container\Exceptions\BindingResolutionException;
use Omega\Container\Exceptions\EntryNotFoundException;
use Psr\Container\ContainerExceptionInterface;
use ReflectionException;

/**
 * BootProviders is responsible for bootstrapping all service providers within the application.
 *
 * It delegates the actual boot process to the Application object. This is typically used
 * during testing or early application setup to ensure all providers are initialized.
 *
 * @category   Omega
 * @package    Application
 * @subpackage Bootstrapper
 * @link       https://omega-mvc.github.io
 * @author     Adriano Giovannini <agisoftt@gmail.com>
 * @copyright  Copyright (c) 2025 - 2026 Adriano Giovannini (https://omega-mvc.github.io)
 * @license    https://www.gnu.org/licenses/gpl-3.0-standalone.html     GPL V3.0+
 * @version    2.0.0
 */
class BootProviders
{
    /**
     * Bootstrap all service providers in the given application instance.
     *
     * @param ApplicationInterface $app The application instance used to resolve provider sources.
     * @return void
     */
    public function bootstrap(ApplicationInterface $app): void
    {
        $app->bootProvider();
    }
}
