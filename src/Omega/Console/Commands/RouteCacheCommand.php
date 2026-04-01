<?php

declare(strict_types=1);

namespace Omega\Console\Commands;

use Omega\Console\AbstractCommand;
use Omega\Router\Router;
use Omega\SerializableClosure\UnsignedSerializableClosure;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputOption;
use function file_exists;
use function file_put_contents;
use function is_callable;
use function serialize;
use function var_export;

#[AsCommand(
    name: 'route:cache',
    description: 'Create a route cache file for faster resolution'
)]
class RouteCacheCommand extends AbstractCommand
{
    protected function configure(): void
    {
        $this->addOption(
            'files',
            null,
            InputOption::VALUE_IS_ARRAY | InputOption::VALUE_OPTIONAL,
            'Cache specific router files'
        );
    }

    public function __invoke(): int
    {
        $io = $this->io;
        $router = $this->app->make(Router::class);

        // Gestione opzione --files
        $files = $this->input->getOption('files');
        if (!empty($files)) {
            $router->reset();
            foreach ($files as $file) {
                $path = $this->app->get('path.base') . $file;
                if (!file_exists($path)) {
                    $io->error("Route file can't be loaded: '$file'");
                    return 1;
                }
                require $path;
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

        $cachePath = $this->app->getApplicationCachePath() . 'route.php';
        $content = '<?php return ' . var_export($routes, true) . ';' . PHP_EOL;

        if (file_put_contents($cachePath, $content) !== false) {
            $io->success('Route cache file has been successfully created.');
            return 0;
        }

        $io->error('Failed to build route cache.');
        return 1;
    }
}
