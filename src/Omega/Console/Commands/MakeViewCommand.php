<?php

declare(strict_types=1);

namespace Omega\Console\Commands;

use Omega\Console\Attribute\AsCommand;
use Omega\Console\Attribute\Make;
use Symfony\Component\Console\Input\InputArgument;

#[AsCommand(
    name: 'make:view',
    description: 'Generate new view template.',
    arguments: [
        'name' => [InputArgument::REQUIRED, 'The name of the view. <bg=red;options=bold>WARNING: This command is case-sensitive: Prova and prova are different)</>']
    ]
)]
#[Make(
    template: __DIR__ . '/../stubs/view.stub',
    path: 'path.view',
    pattern: '__view__',
    suffix: '.template.php',
    target: 'resources.views',
    info: 'View file <options=bold>[__file__name__]</> created successfully.',
    warning: 'View file <options=bold>[__file__name__]</> already exists.',
)]
final class MakeViewCommand extends AbstractMakeCommand
{
}
