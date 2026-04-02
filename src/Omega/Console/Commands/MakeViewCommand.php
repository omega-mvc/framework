<?php

declare(strict_types=1);

namespace Omega\Console\Commands;

use Omega\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputArgument;
use function Omega\Support\slash;

#[AsCommand(
    name: 'make:view',
    description: 'Generate new view template',
    arguments: [
        'name' => [InputArgument::REQUIRED, 'The name of the view']
    ]

)]
final class MakeViewCommand extends AbstractMakeCommand
{
    public function __invoke(): int
    {
        $this->io->info('Making view temlate...');
        $this->isPath('path.view');

        $name = $this->getArgument('name');

        // Passiamo le sostituzioni al template
        $success = $this->makeTemplate($name, [
            'template_location' => slash(path: dirname(__DIR__) . '/stubs/view.stub'),
            'save_location'      => $this->app->get('path.view'),
            'pattern'            => '__view__',
            'suffix'             => '.template.php'
        ]);

        if (!$success) {
            $this->io->error('Failed to create view template');
            return self::FAILURE;
        }

        $this->io->success("Finish created view file.");

        return self::SUCCESS;
    }
}
