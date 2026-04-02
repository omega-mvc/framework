<?php

declare(strict_types=1);

namespace Omega\Console\Commands;

use Omega\Console\Attribute\AsCommand;
use Omega\Text\Str;
use Symfony\Component\Console\Input\InputArgument;
use function Omega\Support\path;
use function Omega\Support\slash;

#[AsCommand(
    name: 'make:controller',
    description: 'Generate new controller class',
    arguments: [
        'name' => [InputArgument::REQUIRED, 'The name of the controller']
    ]
)]
final class MakeControllerCommand extends AbstractMakeCommand
{
    public function __invoke(): int
    {
        $this->io->info('Making controller file...');
        $this->isPath('path.controller');

        $name = $this->getArgument('name');

        $viewName  = Str::toKebabCase($name);

        // Passiamo le sostituzioni al template
        $success = $this->makeTemplate($name, [
            'template_location' => slash(path: dirname(__DIR__) . '/stubs/controller.stub'),
            'save_location'      => $this->app->get('path.controller'),
            'pattern'            => '__controller__',
            'suffix'             => 'Controller.php',
            'vars'              => [
                '__view_name__' => $viewName,
            ]
        ]);

        if (!$success) {
            $this->io->error('Failed to create controller file');
            return self::FAILURE;
        }

        $path = path('app.Http/Controllers') . $name . 'Controller.php';
        $this->io->success("Controller [$path] created successfully.");

        return self::SUCCESS;
    }
}
