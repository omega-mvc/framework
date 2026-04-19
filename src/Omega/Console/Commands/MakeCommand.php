<?php

declare(strict_types=1);

namespace Omega\Console\Commands;

use Omega\Console\Attribute\AsCommand;
use Omega\Console\Attribute\Make;
use Symfony\Component\Console\Input\InputArgument;

#[AsCommand(
    name: 'make:command',
    description: 'Generate new command class',
    arguments: [
        'name' => [InputArgument::REQUIRED, 'The name of the command']
    ]
)]
#[Make(
    template: __DIR__ . '/../stubs/command.stub',
    path: 'path.command',
    pattern: '__command__',
    suffix: 'Command.php',
    target: 'app.Console.Commands',
    info: 'Command <options=bold>[__file__name__]</> created successfully.',
    warning: 'Command <options=bold>[__file__name__]</> already exists.',
    vars: [
        '__name__' => 'kebab'
    ]
)]
final class MakeCommand extends AbstractMakeCommand
{
}
