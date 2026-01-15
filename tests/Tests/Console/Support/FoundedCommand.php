<?php

declare(strict_types=1);

namespace Tests\Console\Support;

use Omega\Application\Application;
use Omega\Console\AbstractCommand;

use function Omega\Console\style;

class FoundedCommand extends AbstractCommand
{
    public function main(): int
    {
        style('command has founded')->out();

        return 0;
    }

    public function default(): int
    {
        style($this->default)->out(false);

        return 0;
    }

    public function returnVoid(Application $app): void
    {
    }
}
