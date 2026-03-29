<?php

declare(strict_types=1);

namespace Omega\Console\Commands;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputArgument;
use Omega\Console\AbstractCommand;

use function Omega\Support\path;
use function Omega\Support\slash;

#[AsCommand(
    name: 'make:middleware',
    description: 'Generate new middleware class'
)]
final class MakeMiddlewareCommand extends AbstractCommand
{
    protected function configure(): void
    {
        $this->addArgument('name', InputArgument::REQUIRED);
    }

    protected function handle(): int
    {
        $this->info('Making middleware file...');
        $this->isPath('path.middleware');

        $name = $this->argument('name');

        // Passiamo le sostituzioni al template
        $success = $this->makeTemplate($name, [
            'template_location' => slash(path: dirname(__DIR__) . '/stubs/middleware.stub'),
            'save_location'      => $this->app->get('path.middleware'),
            'pattern'            => '__middleware__', // Questo sostituisce la classe
            'suffix'             => 'Middleware.php'
        ]);

        if (!$success) {
            $this->error('Failed to create middleware file');
            return self::FAILURE;
        }

        $path = path('app.Middleware') . $name . 'Middleware.php';
        $this->success("Middleware [$path] created successfully.");

        return self::SUCCESS;
    }
}
