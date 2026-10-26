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

use Omega\Database\Schema\Table\Raw;
use Tests\Console\Commands\Fixtures\MissingTableSchemaConnection;
use Tests\Console\Commands\Fixtures\ScriptedSchemaConnection;

return [
    'up' => [
        new Raw('CREATE TABLE missing (id INT)', ScriptedSchemaConnection::shared('missing_table_up')),
    ],
    'down' => [
        new Raw('DROP TABLE missing', new MissingTableSchemaConnection()),
    ],
];
