<?php

declare(strict_types=1);

namespace Omega\Console\Commands;

use Omega\Console\AbstractCommand;
use Omega\Router\Router;
use Symfony\Component\Console\Attribute\AsCommand;

#[AsCommand(
    name: 'route:list',
    description: 'List all registered application routes with HTTP methods, names, and URIs'
)]
class RouteCommand extends AbstractCommand
{
    public function __invoke(): int
    {
        $this->io->title('Route List');

        $routes = Router::getRoutes();

        $rows = array_map(
            fn(array $route) => [
                $this->formatMethods($route['method']),
                $route['name'] ?? '',
                strlen($route['expression']) > 30
                    ? substr($route['expression'], 0, 30) . '…'
                    : $route['expression'],
            ],
            $routes
        );

        $this->io->table(
            ['Method', 'Name', 'URI'],
            $rows
        );

        $this->io->success(count($rows) . ' routes listed.');

        return 0;
    }

    private function formatMethods(array|string $methods): string
    {
        $methods = is_array($methods) ? $methods : [$methods];

        return implode(
            '|',
            array_map(
                fn(string $method) => $this->colorMethod($method),
                $methods
            )
        );
    }

    private function colorMethod(string $method): string
    {
        $method = strtoupper($method);

        $colors = [
            'GET'    => 'blue',
            'HEAD'   => 'blue',
            'POST'   => 'yellow',
            'PUT'    => 'yellow',
            'DELETE' => 'red',
        ];

        $color = $colors[$method] ?? 'gray';

        return "<fg={$color}>{$method}</>";
    }
}
