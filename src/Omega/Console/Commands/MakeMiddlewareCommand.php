<?php

declare(strict_types=1);

namespace Omega\Console\Commands;

use Omega\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputArgument;

use function Omega\Support\path;
use function Omega\Support\slash;

#[AsCommand(
    name: 'make:middleware',
    description: 'Generate new middleware class',
    arguments: [
        'name' => [InputArgument::REQUIRED, 'The name of the middleware']
    ]
)]
final class MakeMiddlewareCommand extends AbstractMakeCommand
{
    public function __invoke(): int
    {
        $this->io->info('Making middleware file...');
        $this->isPath('path.middleware');

        $name = $this->getArgument('name');

        $success = $this->makeTemplate($name, [
            'template_location' => slash(path: dirname(__DIR__) . '/stubs/middleware.stub'),
            'save_location'      => $this->app->get('path.middleware'),
            'pattern'            => '__middleware__',
            'suffix'             => 'Middleware.php'
        ]);

        if (!$success) {
            $this->io->error('Failed to create middleware file');
            return self::FAILURE;
        }

        $path = path('app.Middleware') . $name . 'Middleware.php';
        $this->io->success("Middleware [$path] created successfully.");

        return self::SUCCESS;
    }
}
