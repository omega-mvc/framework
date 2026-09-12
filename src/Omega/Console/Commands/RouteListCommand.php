<?php

declare(strict_types=1);

namespace Omega\Console\Commands;

use Omega\Console\AbstractCommand;
use Omega\Console\Attribute\AsCommand;
use Omega\Console\Traits\InteractsWithConsoleOutputTrait;
use Omega\Router\Router;
use Symfony\Component\Console\Helper\Helper;

use function array_column;
use function array_map;
use function count;
use function implode;
use function is_array;
use function str_repeat;
use function strtoupper;

#[AsCommand(
    name: 'route:list',
    description: 'List all registered application routes with HTTP methods, names, and URIs'
)]
final class RouteListCommand extends AbstractCommand
{
    use InteractsWithConsoleOutputTrait;

    public function __invoke(): int
    {
        $routes = Router::getRoutes();
        if (empty($routes)) {
            $this->io->warning('No routes found.');
            return self::SUCCESS;
        }

        // 1. Format the route data
        $formattedRoutes = array_map(function (array $route): array {
            $methods = is_array($route['method']) ? $route['method'] : [$route['method']];
            return [
                'method' => $this->formatMethods($methods),
                'uri'    => $route['uri'] ?? $route['expression'] ?? '',
                'name'   => $route['name']
            ];
        }, $routes);

        // 2. Compute the width of the left-hand column (methods)
        $maxMethodWidth = $this->getVisibleMaxWidth(array_column($formattedRoutes, 'method'));

        foreach ($formattedRoutes as $route) {
            // Pad the URI to align the method column
            $methodVisibleWidth = $this->getVisibleWidth($route['method']);
            $padding = str_repeat(' ', $maxMethodWidth - $methodVisibleWidth + 1);

            $leftSide = "{$route['method']}{$padding}<fg=cyan>{$route['uri']}</>";
            $rightSide = $route['name'] ? "<comment>{$route['name']}</comment>" : "";

            $this->componentsTwoColumns($leftSide, $rightSide);
        }

        // 3. Final summary
        $count = count($routes);
        $summary = "<fg=blue;options=bold>Showing [{$count}] routes.</>";

        // Use writeRight without PHP_EOL inside (use newLine first)
        $this->io->newLine();
        $this->writeRight($summary, 2);

        return self::SUCCESS;
    }

    /**
     * Format a list of HTTP methods into a colorized, pipe-separated string.
     *
     * @param array<int, string> $methods The HTTP methods to format.
     * @return string The colorized methods string.
     */
    private function formatMethods(array $methods): string
    {
        return implode('|', array_map(fn(string $m): string => $this->colorMethod($m), $methods));
    }

    private function colorMethod(string $method): string
    {
        $method = strtoupper($method);

        $colors = [
            'GET'     => 'blue',
            'HEAD'    => 'cyan',
            'POST'    => 'yellow',
            'PUT'     => 'magenta',
            'PATCH'   => 'green',
            'DELETE'  => 'red',
            'OPTIONS' => 'white',
            'TRACE'   => 'gray',
            'CONNECT' => 'black',
        ];

        $color = $colors[$method] ?? 'gray';

        return "<fg={$color}>{$method}</>";
    }
}
