<?php

/**
 * Part of Omega - Tests\Console Package.
 *
 * @link      https://omega-mvc.github.io
 * @author    Adriano Giovannini <agisoftt@gmail.com>
 * @copyright Copyright (c) 2025 - 2026 Adriano Giovannini (https://omega-mvc.github.io)
 * @license   GPL-3.0-or-later
 * @version   2.0.0
 */

declare(strict_types=1);

namespace Tests\Console\Fixtures;

use Symfony\Component\Console\Command\Command;

/**
 * Plain Symfony command used to cover the non-AbstractCommand loader branch.
 */
class PlainCommand extends Command
{
    public function __construct()
    {
        parent::__construct('plain:run');
    }

    protected function execute(\Symfony\Component\Console\Input\InputInterface $input, \Symfony\Component\Console\Output\OutputInterface $output): int
    {
        $output->writeln('plain');

        return self::SUCCESS;
    }
}
