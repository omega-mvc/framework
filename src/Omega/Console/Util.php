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

namespace Omega\Console;

use Omega\Application\Application;
use Omega\Config\ConfigRepository;

/**
 * Utility class for console-related helpers.
 *
 * @category  Omega
 * @package   Console
 * @link      https://omega-mvc.github.io
 * @author    Adriano Giovannini <agisoftt@gmail.com>
 * @copyright Copyright (c) 2025 - 2026 Adriano Giovannini (https://omega-mvc.github.io)
 * @license   https://www.gnu.org/licenses/gpl-3.0-standalone.html     GPL V3.0+
 * @version   2.0.0
 */
final class Util
{
    /**
     * Load all registered console commands from the configuration.
     *
     * @param Application $app The application container instance.
     * @return CommandMap[] An array of command mappings ready to be registered.
     */
    public static function loadCommandFromConfig(Application $app): array
    {
        $commandMap = [];
        foreach ($app[ConfigRepository::class]->get('commands', []) as $commands) {
            foreach ($commands as $command) {
                $commandMap[] = new CommandMap($command);
            }
        }

        return $commandMap;
    }
}
