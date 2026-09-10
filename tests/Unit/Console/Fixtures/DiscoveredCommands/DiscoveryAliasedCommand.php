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

namespace App\Console\Commands;

use Omega\Console\AbstractCommand;
use Omega\Console\Attribute\AsCommand;

/**
 * Discoverable command with aliases.
 */
#[AsCommand(name: 'discover:two', aliases: ['d2', 'd3'])]
class DiscoveryAliasedCommand extends AbstractCommand
{
    public function __invoke(): int
    {
        return self::SUCCESS;
    }
}
