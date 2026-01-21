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

namespace Omega\Database\Schema\DB;

use Omega\Database\Schema\Query;
use Omega\Database\Schema\SchemaConnectionInterface;
use Omega\Database\Schema\Traits\ConditionTrait;

/**
 * Class Create
 *
 * Handles the generation of a CREATE DATABASE SQL statement.
 * Uses ConditionTrait for optional conditions (e.g., IF NOT EXISTS).
 *
 * @category   Omega
 * @package    Database
 * @subpackage Schema\DB
 * @link       https://omega-mvc.github.io
 * @author     Adriano Giovannini <agisoftt@gmail.com>
 * @copyright  Copyright (c) 2025 - 2026 Adriano Giovannini (https://omega-mvc.github.io)
 * @license    https://www.gnu.org/licenses/gpl-3.0-standalone.html     GPL V3.0+
 * @version    2.0.0
 */
class Create extends Query
{
    use ConditionTrait;

    /** @var string Name of the database to create */
    private string $databaseName;

    /**
     * Create constructor.
     *
     * @param string                    $databaseName Name of the database
     * @param SchemaConnectionInterface $pdo          Database connection interface
     */
    public function __construct(string $databaseName, SchemaConnectionInterface $pdo)
    {
        $this->databaseName = $databaseName;
        $this->pdo          = $pdo;
    }

    /**
     * Build the CREATE DATABASE SQL statement.
     *
     * @return string SQL query string
     */
    protected function builder(): string
    {
        $condition = $this->join([$this->ifExists, $this->databaseName]);

        return 'CREATE DATABASE ' . $condition . ';';
    }
}
