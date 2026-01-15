<?php

/**
 * Part of Omega - Database Package.
 *
 * @link      https://omega-mvc.github.io
 * @author    Adriano Giovannini <agisoftt@gmail.com>
 * @copyright Copyright (c) 2025 - 2026 Adriano Giovannini (https://omega-mvc.github.io)
 * @license   https://www.gnu.org/licenses/gpl-3.0-standalone.html     GPL V3.0+
 * @version   2.0.0
 */

declare(strict_types=1);

namespace Omega\Database\Seeder;

use Omega\Database\Query\Insert;

/**
 * Interface SeederInterface
 *
 * Defines the contract for database seeders.
 * Seeders are responsible for populating tables with initial or test data.
 *
 * @category   Omega
 * @package    Database
 * @subpackage Seeder
 * @link       https://omega-mvc.github.io
 * @author     Adriano Giovannini <agisoftt@gmail.com>
 * @copyright  Copyright (c) 2025 - 2026 Adriano Giovannini (https://omega-mvc.github.io)
 * @license    https://www.gnu.org/licenses/gpl-3.0-standalone.html     GPL V3.0+
 * @version    2.0.0
 */
interface SeederInterface
{
    /**
     * Call another seeder or migration class.
     *
     * This allows chaining or reusing seeders by calling their class directly.
     *
     * @param class-string $className Fully qualified class name of the seeder or migration.
     * @return void
     */
    public function call(string $className): void;

    /**
     * Create a new database insert operation.
     *
     * Provides an `Insert` object to define data for a specific table.
     *
     * @param string $tableName Name of the table to insert data into.
     * @return Insert Insert object ready for adding values and executing the insert.
     */
    public function create(string $tableName): Insert;

    /**
     * Run the seeder.
     *
     * This method contains the logic to populate tables with data.
     *
     * @return void
     */
    public function run(): void;
}
