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
use Omega\Config\ConfigRepository;
use Omega\Console\AbstractCommand;
use Omega\Container\Exceptions\BindingResolutionException;
use Omega\Container\Exceptions\CircularAliasException;
use Omega\Container\Exceptions\EntryNotFoundException;
use Omega\Support\Bootstrap\ConfigProviders;
use Psr\Container\ContainerExceptionInterface;
use ReflectionException;

use function file_exists;
use function file_put_contents;
use function Omega\Console\error;
use function Omega\Console\info;
use function unlink;

use const PHP_EOL;

/**
 * Command to manage application configuration cache.
 *
 * Supports building and clearing cached configuration. This command
 * provides two patterns:
 * - `config:cache` to build the configuration cache.
 * - `config:clear` to remove the cached configuration.
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
class ConfigCommand extends AbstractCommand
{
    /**
     * Command registration configuration.
     *
     * Defines the pattern used to invoke the command and the method to execute.
     *
     * @var array<int, array<string, mixed>>
     */
    public static array $command = [
        [
            'pattern' => 'config:cache',
            'fn'      => [self::class, 'main'],
        ], [
            'pattern' => 'config:clear',
            'fn'      => [self::class, 'clear'],
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
                'config:cache' => 'Build cache application config',
                'config:clear' => 'Remove cached application config',
            ],
            'options'   => [],
            'relation'  => [],
        ];
    }

    /**
     * Executes the command to build the application configuration cache.
     *
     * Bootstraps the configuration providers, clears any existing cached configuration,
     * and writes the new configuration array to a PHP file in the cache directory.
     *
     * @return int Exit code: 0 on success, 1 on failure.
     * @throws BindingResolutionException Thrown when resolving a binding fails.
     * @throws CircularAliasException Thrown when alias resolution loops recursively.
     * @throws ContainerExceptionInterface Thrown on general container errors, e.g., service not retrievable.
     * @throws EntryNotFoundException Thrown when no entry exists for the requested identifier.
     * @throws ReflectionException Thrown when the requested class or interface cannot be reflected.
     */
    public function main(): int
    {
        $app = Application::getInstance();
        new ConfigProviders()->bootstrap($app);

        $this->clear();
        $config       = $app->get(ConfigRepository::class)->getAll();
        $cachedConfig = '<?php return ' . var_export($config, true) . ';' . PHP_EOL;
        if (file_put_contents($app->getApplicationCachePath() . 'config.php', $cachedConfig)) {
            info('Configuration cached successfully.')->out();

            return 0;
        }
        error('Cant build config cache.')->out();

        return 1;
    }

    /**
     * Clears the cached configuration file.
     *
     * If the configuration cache exists, it will be removed.
     *
     * @return int Exit code: 0 if cache was cleared, 1 if no cache existed.
     * @throws BindingResolutionException Thrown when resolving a binding fails.
     * @throws CircularAliasException Thrown when alias resolution loops recursively.
     * @throws ContainerExceptionInterface Thrown on general container errors, e.g., service not retrievable.
     * @throws EntryNotFoundException Thrown when no entry exists for the requested identifier.
     * @throws ReflectionException Thrown when the requested class or interface cannot be reflected.
     */
    public function clear(): int
    {
        if (file_exists($file = Application::getInstance()->getApplicationCachePath() . 'config.php')) {
            @unlink($file);
            info('Configuration cache cleared successfully.')->out();

            return 0;
        }

        return 1;
    }
}
