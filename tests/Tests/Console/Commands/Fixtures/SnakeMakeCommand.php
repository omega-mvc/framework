<?php

declare(strict_types=1);

/**
 * Part of Omega - Tests\Console\Commands\Fixtures.
 *
 * @link      https://omega-mvc.github.io
 * @author    Axel Magold <axel.magold@gmail.com>
 * @copyright Copyright (c) 2025 - 2026 Axel Magold (https://omega-mvc.github.io)
 * @license   https://www.gnu.org/licenses/gpl-3.0-standalone.html     GPL V3.0+
 * @version   2.0.0
 */

namespace Tests\Console\Commands\Fixtures;

use Omega\Console\Attribute\AsCommand;
use Omega\Console\Attribute\Make;
use Omega\Console\Commands\AbstractMakeCommand;
use Symfony\Component\Console\Input\InputArgument;

#[AsCommand(
    name: 'make:snake',
    description: 'Generate a file using snake case template variables',
    arguments: [
        'name' => [InputArgument::REQUIRED, 'The name of the file to create'],
    ]
)]
#[Make(
    template: __DIR__ . '/stubs/snake.stub',
    path: 'path.snake',
    pattern: '__name__',
    suffix: '.txt',
    target: 'app.Snake',
    info: 'Snake <options=bold>[__file__name__]</> created successfully.',
    warning: 'Snake <options=bold>[__file__name__]</> already exists.',
    vars: [
        '__snake__' => 'snake',
        '__kebab__' => 'kebab',
        '__plain__' => 'untouched',
    ]
)]
final class SnakeMakeCommand extends AbstractMakeCommand
{
}
