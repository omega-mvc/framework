<?php

declare(strict_types=1);

namespace Omega\Console\Commands;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputArgument;
use function Omega\Support\path;
use function Omega\Support\slash;

#[AsCommand(
    name: 'make:provider',
    description: 'Generate new service provider class'
)]
final class MakeProviderCommand extends AbstractMakeCommand
{
    protected function configure(): void
    {
        $this->addArgument('name', InputArgument::REQUIRED);
    }

    public function __invoke(): int
    {
        $this->io->info('Making a service provider class...');
        $this->isPath('path.provider');

        $name = $this->getArgument('name');

        // Passiamo le sostituzioni al template
        $success = $this->makeTemplate($name, [
            'template_location' => slash(path: dirname(__DIR__) . '/stubs/provider.stub'),
            'save_location'      => $this->app->get('path.provider'),
            'pattern'            => '__provider__', // Questo sostituisce la classe
            'suffix'             => 'ServiceProvider.php'
        ]);

        if (!$success) {
            $this->io->error('Failed to create service provider class.');
            return self::FAILURE;
        }

        $path = path('app.Providers') . $name . 'ServiceProvider.php';
        $this->io->success("ServiceProvider [$path] created successfully.");

        return self::SUCCESS;
    }
}
