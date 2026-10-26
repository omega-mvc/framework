<?php

declare(strict_types=1);

/*
 * Part of Omega - Tests\Console\Commands Package.
 *
 * @link      https://omegamvc.github.io
 * @author    Axel Magold <axel.magold@gmail.com>
 * @copyright 2025-2026 The Omega MVC Framework
 * @license   https://opensource.org/license/mit The MIT License
 * @version   2.0.0
 */

namespace Tests\Console\Commands\Fixtures;

use RuntimeException;

class MissingTableSchemaConnection extends ScriptedSchemaConnection
{
    public function query(string $query, ?array $bindings = null): static
    {
        throw new RuntimeException('Base table or view not found: 1146 Table \'omega_test.users\' doesn\'t exist');
    }
}
