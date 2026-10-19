<?php

declare(strict_types=1);

namespace Tests\Event\Support;

use Omega\Database\ConnectionInterface;
use Omega\Database\Model\Model;
use PHPUnit\Framework\Attributes\CoversClass;

/**
 * A Model stub with a fixed table name that avoids database queries from magic accessors.
 */
#[CoversClass(Model::class)]
class StubModel extends Model
{
    /**
     * Creates a model with a determinable table name.
     *
     * @param ConnectionInterface $pdo    The database connection.
     * @param array<array<array-key, mixed>> $column The initial column data.
     * @param string              $table  Optional explicit table name.
     */
    public function __construct(ConnectionInterface $pdo, array $column, string $table = '')
    {
        if ($table !== '') {
            $this->tableName = $table;
        }

        parent::__construct($pdo, $column);
    }

    /**
     * Reports the table name as existing without touching the database.
     *
     * @param string $name The property name being checked.
     * @return bool True for the tableName property, false otherwise.
     */
    public function __isset(string $name): bool
    {
        return $name === 'tableName';
    }

    /**
     * Returns the table name without querying the database.
     *
     * @param string $name The property name being accessed.
     * @return mixed The table name or the parent implementation result.
     */
    public function __get(string $name): mixed
    {
        if ($name === 'tableName') {
            return $this->tableName;
        }

        return parent::__get($name);
    }
}