<?php

declare(strict_types=1);

namespace Omega\Console\Commands;

use Omega\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputArgument;
use function Omega\Support\path;
use function Omega\Support\slash;

#[AsCommand(
    name: 'make:exception',
    description: 'Generate new exception class',
    arguments: [
        'name' => [InputArgument::REQUIRED, 'The name of the exception']
    ]
)]
final class MakeExceptionCommand extends AbstractMakeCommand
{
    public function __invoke(): int
    {
        $this->io->info('Making exception file...');
        $this->isPath('path.exception');

        $name = $this->getArgument('name');

        // Passiamo le sostituzioni al template
        $success = $this->makeTemplate($name, [
            'template_location' => slash(path: dirname(__DIR__) . '/stubs/exception.stub'),
            'save_location'      => $this->app->get('path.exception'),
            'pattern'            => '__exception__', // Questo sostituisce la classe
            'suffix'             => 'Exception.php'
        ]);

        if (!$success) {
            $this->io->error('Failed to create exception file');
            return self::FAILURE;
        }

        $path = path('app.Exceptions') . $name . 'Exception.php';
        $this->io->success("Exception [$path] created successfully.");

        return self::SUCCESS;
    }
}
