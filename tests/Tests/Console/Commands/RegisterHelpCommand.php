<?php

declare(strict_types=1);

namespace Tests\Console\Commands;

use Omega\Console\AbstractCommand;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(AbstractCommand::class)]
class RegisterHelpCommand extends AbstractCommand
{
    /**
     * {@inheritdoc}
     */
    public function printHelp(): array
    {
        return [
            'commands'  => [
                'test' => 'some test will appear in test',
            ],
            'options'   => [
                '--test' => 'this also will display in test',
            ],
            'relation'  => [
                'test' => ['[unit]'],
            ],
        ];
    }
}
