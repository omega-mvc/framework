<?php

declare(strict_types=1);

namespace Omega\Console\Commands;

use Omega\Console\Attribute\AsCommand;
use Omega\Console\Attribute\Make;
use Symfony\Component\Console\Input\InputArgument;

#[AsCommand(
    name: 'make:exception',
    description: 'Generate new exception class',
    arguments: [
        'name' => [InputArgument::REQUIRED, 'The name of the exception']
    ]
)]
#[Make(
    template: __DIR__ . '/../stubs/exception.stub',
    path: 'path.exception',
    pattern: '__exception__',
    suffix: 'Exception.php',
    target: 'app.Exceptions',
    info: 'Exception <options=bold>[__file__name__]</> created successfully.',
    warning: 'Exception <options=bold>[__file__name__]</> already exists.',
)]
final class MakeExceptionCommand extends AbstractMakeCommand
{
}
