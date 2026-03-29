<?php

declare(strict_types=1);

namespace Omega\Console\Commands;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputArgument;
use Omega\Console\AbstractCommand;

use function Omega\Support\path;
use function Omega\Support\slash;

#[AsCommand(
    name: 'make:exception',
    description: 'Generate new exception class'
)]
final class MakeExceptionCommand extends AbstractCommand
{
    protected function configure(): void
    {
        $this->addArgument('name', InputArgument::REQUIRED);
    }

    protected function handle(): int
    {
        $this->info('Making exception file...');
        $this->isPath('path.exception');

        $name = $this->argument('name');

        // Passiamo le sostituzioni al template
        $success = $this->makeTemplate($name, [
            'template_location' => slash(path: dirname(__DIR__) . '/stubs/exception.stub'),
            'save_location'      => $this->app->get('path.exception'),
            'pattern'            => '__exception__', // Questo sostituisce la classe
            'suffix'             => 'Exception.php'
        ]);

        if (!$success) {
            $this->error('Failed to create exception file');
            return self::FAILURE;
        }

        $path = path('app.Exception') . $name . 'Exception.php';
        $this->success("Exception [$path] created successfully.");

        return self::SUCCESS;
    }
}
