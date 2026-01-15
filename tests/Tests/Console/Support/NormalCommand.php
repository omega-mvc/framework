<?php

declare(strict_types=1);

namespace Tests\Console\Support;

use Omega\Application\Application;
use Omega\Console\CommandMap;
use Omega\Console\Console;

class NormalCommand extends Console
{
    public function __construct(Application $app)
    {
        parent::__construct($app);
    }

    protected function commands(): array
    {
        return [
            // old style
            new CommandMap([
                'cmd'     => 'use:full',
                'mode'    => 'full',
                'class'   => FoundedCommand::class,
                'fn'      => 'main',
            ]),
            new CommandMap([
                'cmd'     => ['use:group', 'group'],
                'class'   => FoundedCommand::class,
                'fn'      => 'main',
            ]),
            new CommandMap([
                'cmd'     => 'start:',
                'mode'    => 'start',
                'class'   => FoundedCommand::class,
                'fn'      => 'main',
            ]),
            new CommandMap([
                'cmd'     => 'use:without_mode',
                'class'   => FoundedCommand::class,
                'fn'      => 'main',
            ]),
            new CommandMap([
                'cmd'     => 'use:without_main',
                'class'   => FoundedCommand::class,
            ]),
            new CommandMap([
                'match'   => fn ($given) => $given == 'use:match',
                'fn'      => [FoundedCommand::class, 'main'],
            ]),
            new CommandMap([
                'pattern' => 'use:pattern',
                'fn'      => [FoundedCommand::class, 'main'],
            ]),
            new CommandMap([
                'pattern' => ['pattern1', 'pattern2'],
                'fn'      => [FoundedCommand::class, 'main'],
            ]),
            new CommandMap([
                'pattern' => 'use:default_option',
                'fn'      => [FoundedCommand::class, 'default'],
                'default' => [
                    'default' => 'test',
                ],
            ]),
            new CommandMap([
                'pattern' => 'use:no-int-return',
                'fn'      => [FoundedCommand::class, 'returnVoid'],
            ]),
        ];
    }
}
