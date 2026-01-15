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
use Omega\Application\Application;
use Omega\Container\Exceptions\BindingResolutionException;
use Omega\Container\Exceptions\CircularAliasException;
use Omega\Container\Exceptions\EntryNotFoundException;
use Omega\Router\Router;
use Omega\SerializableClosure\UnsignedSerializableClosure;
use Psr\Container\ContainerExceptionInterface;
use ReflectionException;

use function Omega\Console\error;
use function Omega\Console\success;
use function Omega\Console\warn;

/**
 * RouteCacheCommand
 *
 * This command manages the caching of application routes.
 * It provides two main operations:
 * 1. cache - Builds and stores a route cache file, optionally for specific router files.
 * 2. clear - Removes the existing route cache file.
 *
 * The class outputs status messages indicating success or failure of operations.
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
class RouteCacheCommand extends AbstractCommand
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
            'pattern' => 'route:cache',
            'fn'      => [self::class, 'cache'],
        ], [
            'pattern' => 'route:clear',
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
                'route:cache' => 'Build route cache',
                'route:clear' => 'Remove route cache',
            ],
            'options'   => [
                '--files' => 'Cache specific router files.',
            ],
            'relation'  => [
                'route:cache' => ['--files'],
            ],
        ];
    }

    /**
     * Build and store the route cache.
     *
     * This method retrieves routes from the Router instance, optionally loads
     * specific route files, serializes closures when needed, and saves the
     * resulting route array into a cache file for faster route resolution.
     *
     * @param Application $app    The application instance providing paths and services
     * @param Router      $router The router instance containing registered routes
     * @return int Returns 0 on successful cache creation, 1 on failure
     * @throws BindingResolutionException Thrown when resolving a binding fails.
     * @throws CircularAliasException Thrown when alias resolution loops recursively.
     * @throws ContainerExceptionInterface Thrown on general container errors, e.g., service not retrievable.
     * @throws EntryNotFoundException Thrown when no entry exists for the identifier.
     * @throws ReflectionException Thrown when the requested class or interface cannot be reflected.
     */
    public function cache(Application $app, Router $router): int
    {
        if (false !== ($files = $this->option('files', false))) {
            $router->reset();
            $files = is_string($files) ? [$files] : $files;
            foreach ($files as $file) {
                if (false === file_exists(get_path('path.base') . $file)) {
                    warn("Route file cant be load '$file'.")->out();

                    return 1;
                }

                require get_path('path.base') . $file;
            }
        }

        $routes = [];

        foreach ($router->getRoutesRaw() as $route) {
            $routes[] = [
                'method'     => $route['method'],
                'uri'        => $route['uri'],
                'expression' => $route['expression'],
                'function'   => is_callable($route['function'])
                    ? serialize(new UnsignedSerializableClosure($route['function']))
                    : $route['function'],
                'middleware' => $route['middleware'],
                'name'       => $route['name'],
                'patterns'   => $route['patterns'] ?? [],
            ];
        }

        $cached_route = '<?php return ' . var_export($routes, true) . ';' . PHP_EOL;
        if (file_put_contents($app->getApplicationCachePath() . 'route.php', $cached_route)) {
            success('Route file has successfully created.')->out();

            return 0;
        }
        error('Cant build route cache.')->out();

        return 1;
    }

    /**
     * Remove the route cache file.
     *
     * This method deletes the cached route file from the application cache directory.
     * It prints a success message if the file existed and was removed, otherwise returns 1.
     *
     * @param Application $app The application instance providing paths and services
     * @return int Returns 0 if the cache file was successfully removed, 1 if the file does not exist
     * @throws BindingResolutionException Thrown when resolving a binding fails.
     * @throws CircularAliasException Thrown when alias resolution loops recursively.
     * @throws ContainerExceptionInterface Thrown on general container errors, e.g., service not retrievable.
     * @throws EntryNotFoundException Thrown when no entry exists for the identifier.
     * @throws ReflectionException Thrown when the requested class or interface cannot be reflected.
     */
    public function clear(Application $app): int
    {
        if (file_exists($file = $app->getApplicationCachePath() . 'route.php')) {
            @unlink($file);
            success('Clear route cache has successfully.')->out();

            return 0;
        }

        return 1;
    }
}
