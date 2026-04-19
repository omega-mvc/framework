<?php

declare(strict_types=1);

namespace Omega\Console\Commands;

use Omega\Console\Attribute\AsCommand;
use Omega\Console\Attribute\Make;
use Symfony\Component\Console\Input\InputArgument;

#[AsCommand(
    name: 'make:provider',
    description: 'Generate new service provider class',
    arguments: [
        'name' => [InputArgument::REQUIRED, 'The name of the provider']
    ]
)]
#[Make(
    template: __DIR__ . '/../stubs/provider.stub',
    path: 'path.provider',
    pattern: '__provider__',
    suffix: 'Provider.php',
    target: 'app.Provider',
    info: 'ServiceProvider <options=bold>[__file__name__]</> created successfully.',
    warning: 'ServiceProvider <options=bold>[__file__name__]</> already exists.',
)]
final class MakeProviderCommand extends AbstractMakeCommand
{
}
