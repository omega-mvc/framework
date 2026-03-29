<?php

declare(strict_types=1);

namespace Omega\Console\Commands;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputArgument;
use Omega\Console\AbstractCommand;

use function Omega\Support\path;
use function Omega\Support\slash;

#[AsCommand(
    name: 'make:view',
    description: 'Generate new view template'
)]
final class MakeViewCommand extends AbstractCommand
{
    protected function configure(): void
    {
        $this->addArgument('name', InputArgument::REQUIRED);
    }

    protected function handle(): int
    {
        $this->info('Making view temlate...');
        $this->isPath('path.view');

        $name = $this->argument('name');

        // Passiamo le sostituzioni al template
        $success = $this->makeTemplate($name, [
            'template_location' => slash(path: dirname(__DIR__) . '/stubs/view.stub'),
            'save_location'      => $this->app->get('path.view'),
            'pattern'            => '__view__', // Questo sostituisce la classe
            'suffix'             => '.template.php'
        ]);

        if (!$success) {
            $this->error('Failed to create view template');
            return self::FAILURE;
        }

        $this->success("Finish created view file.");

        return self::SUCCESS;
    }
}
