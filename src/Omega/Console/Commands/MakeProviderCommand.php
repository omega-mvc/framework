<?php

declare(strict_types=1);

namespace Omega\Console\Commands;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputArgument;
use Omega\Console\AbstractCommand;

use function Omega\Support\path;
use function Omega\Support\slash;

#[AsCommand(
    name: 'make:provider',
    description: 'Generate new service provider class'
)]
final class MakeProviderCommand extends AbstractCommand
{
    protected function configure(): void
    {
        $this->addArgument('name', InputArgument::REQUIRED);
    }

    protected function handle(): int
    {
        $this->info('Making a service provider class...');
        $this->isPath('path.provider');

        $name = $this->argument('name');

        // Passiamo le sostituzioni al template
        $success = $this->makeTemplate($name, [
            'template_location' => slash(path: dirname(__DIR__) . '/stubs/provider.stub'),
            'save_location'      => $this->app->get('path.provider'),
            'pattern'            => '__provider__', // Questo sostituisce la classe
            'suffix'             => 'ServiceProvider.php'
        ]);

        if (!$success) {
            $this->error('Failed to create service provider class.');
            return self::FAILURE;
        }

        $path = path('app.Providers') . $name . 'ServiceProvider.php';
        $this->success("ServiceProvider [$path] created successfully.");

        return self::SUCCESS;
    }
}
