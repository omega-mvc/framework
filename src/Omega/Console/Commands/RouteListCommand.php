<?php

declare(strict_types=1);

namespace Omega\Console\Commands;

use Omega\Console\AbstractCommand;
use Omega\Console\Attribute\AsCommand;
use Omega\Console\Traits\InteractsWithConsoleOutputTrait;
use Omega\Router\Router;
use Symfony\Component\Console\Helper\Helper;

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

        // 1. Prepariamo i dati formattati
        $formattedRoutes = array_map(function($route) {
            $methods = is_array($route['method']) ? $route['method'] : [$route['method']];
            return [
                'method' => $this->formatMethods($methods),
                'uri'    => $route['expression'],
                'name'   => $route['name'] ?? ''
            ];
        }, $routes);

        // 2. Calcoliamo lo spazio per la colonna sinistra (Metodi)
        $maxMethodWidth = $this->getVisibleMaxWidth(array_column($formattedRoutes, 'method'));

        foreach ($formattedRoutes as $route) {
            // Calcoliamo il padding per allineare l'URI
            $methodVisibleWidth = $this->getVisibleWidth($route['method']);
            $padding = str_repeat(' ', $maxMethodWidth - $methodVisibleWidth + 1);

            $leftSide = "{$route['method']}{$padding}<fg=cyan>{$route['uri']}</>";
            $rightSide = $route['name'] ? "<comment>{$route['name']}</comment>" : "";

            $this->componentsTwoColumns($leftSide, $rightSide);
        }

        // 3. Summary finale
        $count = count($routes);
        $summary = "<fg=blue;options=bold>Showing [{$count}] routes.</>";

        // Usiamo writeRight senza PHP_EOL dentro (meglio newLine prima)
        $this->io->newLine();
        $this->writeRight($summary, 2);

        return self::SUCCESS;
    }

    private function formatMethods(array $methods): string
    {
        return implode('|', array_map(fn($m) => $this->colorMethod($m), $methods));
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
