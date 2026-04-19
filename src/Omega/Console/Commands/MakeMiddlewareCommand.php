<?php

declare(strict_types=1);

namespace Omega\Console\Commands;

use Omega\Console\Attribute\AsCommand;
use Omega\Console\Attribute\Make;
use Symfony\Component\Console\Input\InputArgument;

#[AsCommand(
    name: 'make:middleware',
    description: 'Generate new middleware class',
    arguments: [
        'name' => [InputArgument::REQUIRED, 'The name of the middleware']
    ]
)]
#[Make(
    template: __DIR__ . '/../stubs/middleware.stub',
    path: 'path.middleware',
    pattern: '__middleware__',
    suffix: 'Middleware.php',
    target: 'app.Middlewares',
    info: 'Middleware <options=bold>[__file__name__]</> created successfully.',
    warning: 'Middleware <options=bold>[__file__name__]</> already exists.',
)]
final class MakeMiddlewareCommand extends AbstractMakeCommand
{
}
