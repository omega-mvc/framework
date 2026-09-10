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

use Omega\Console\AbstractCommand;

/**
 * Command without the AsCommand attribute.
 */
class NoAttributeCommand extends AbstractCommand
{
    public function __invoke(): int
    {
        $this->output->writeln('no-attribute');

        return self::SUCCESS;
    }
}
