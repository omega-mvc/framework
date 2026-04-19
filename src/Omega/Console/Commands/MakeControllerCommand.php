<?php

declare(strict_types=1);

namespace Omega\Console\Commands;

use Omega\Console\Attribute\AsCommand;
use Omega\Console\Attribute\Make;
use Symfony\Component\Console\Input\InputArgument;

#[AsCommand(
    name: 'make:controller',
    description: 'Generate new controller class',
    arguments: [
        'name' => [InputArgument::REQUIRED, 'The name of the controller']
    ]
)]#[Make(
    template: __DIR__ . '/../stubs/controller.stub',
    path: 'path.controller',
    pattern: '__controller__',
    suffix: 'Controller.php',
    target: 'app.Http.Controllers',
    info: 'Controller <options=bold>[__file__name__]</> created successfully.',
    warning: 'Controller <options=bold>[__file__name__]</> already exists.',
    vars: [
        '__name__' => 'kebab'
    ]
)]
final class MakeControllerCommand extends AbstractMakeCommand
{
}
