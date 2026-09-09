<?php

declare(strict_types=1);

namespace Omega\Console\Commands;

use Closure;
use Omega\Console\AbstractCommand;
use Omega\Console\Attribute\AsCommand;
use Omega\Router\Router;
use Omega\SerializableClosure\UnsignedSerializableClosure;
use Symfony\Component\Console\Input\InputOption;
use Throwable;

use function file_put_contents;
use function in_array;
use function is_file;
use function serialize;
use function var_export;

use const PHP_EOL;

#[AsCommand(
    name: 'route:cache',
    description: 'Create a route cache file for faster resolution',
    options: [
        'files' => [null, InputOption::VALUE_IS_ARRAY | InputOption::VALUE_OPTIONAL, 'Cache specific router files']
    ]
)]
class RouteCacheCommand extends AbstractCommand
{
    public function __invoke(): int
    {
        $io = $this->io;
        $router = $this->app->make(Router::class);

        // Handle the --files option
        $files = $this->input->getOption('files');
        if (!empty($files)) {
            $router->reset();

            $requiredFiles = [];

            foreach ($files as $file) {
                $path = $this->app->get('path.base') . $file;

                if (!is_file($path)) {
                    $io->error("Route file can't be loaded: '$file'");
                    return self::FAILURE;
                }

                if (in_array($path, $requiredFiles, true)) {
                    continue;
                }

                try {
                    require $path;
                } catch (Throwable $e) {
                    $io->error('Failed to load route file: ' . $e->getMessage());
                    return self::FAILURE;
                }

                $requiredFiles[] = $path;
            }
        }

        $routes = [];
        foreach ($router->getRoutesRaw() as $route) {
            $routes[] = [
                'method'     => $route['method'],
                'uri'        => $route['uri'],
                'expression' => $route['expression'],
                'function'   => $route['function'] instanceof Closure
                    ? serialize(new UnsignedSerializableClosure($route['function']))
                    : $route['function'],
                'middleware' => $route['middleware'],
                'name'       => $route['name'],
                'patterns'   => $route['patterns'] ?? [],
            ];
        }

        if (empty($routes)) {
            $io->warning('No routes to cache.');
            return self::FAILURE;
        }

        $cachePath = $this->app->getApplicationCachePath() . 'route.php';
        $content = '<?php return ' . var_export($routes, true) . ';' . PHP_EOL;

        if (file_put_contents($cachePath, $content) !== false) {
            $io->info('Route cache file has been successfully created.');
            return self::SUCCESS;
        }

        $io->error('Failed to build route cache.');
        return self::FAILURE;
    }
}
