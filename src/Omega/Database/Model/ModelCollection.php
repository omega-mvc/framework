<?php

/**
 * Part of Omega - Database Package.
 *
 * @link      https://omega-mvc.github.io
 * @author    Adriano Giovannini <agisoftt@gmail.com>
 * @copyright Copyright (c) 2025 - 2026 Adriano Giovannini (https://omega-mvc.github.io)
 * @license   GPL-3.0-or-later
 * @version   2.0.0
 */

declare(strict_types=1);

namespace Omega\Database\Model;

use Exception;
use Omega\Collection\Collection;
use Omega\Database\Query\Delete;
use Omega\Database\Query\Update;
use Traversable;

use function array_merge;
use function array_values;
use function is_int;
use function is_string;
use function iterator_to_array;

/**
 * ModelCollection class.
 *
 * A collection of Model instances with helper methods to
 * perform operations on multiple models at once.
 *
 * @category   Omega
 * @package    Database
 * @subpackage Model
 * @link       https://omega-mvc.github.io
 * @author     Adriano Giovannini <agisoftt@gmail.com>
 * @copyright  Copyright (c) 2025 - 2026 Adriano Giovannini (https://omega-mvc.github.io)
 * @license   GPL-3.0-or-later
 * @version    2.0.0
 *
 * @extends Collection<array-key, Model>
 */
class ModelCollection extends Collection
{
    /** @var Model Reference model used to infer table and primary key. */
    private Model $model;

    /**
     * Construct a ModelCollection.
     *
     * @param iterable<array-key, Model> $models Models to initialize the collection with.
     * @param Model $of Reference model to infer table and primary key.
     */
    public function __construct(iterable $models, Model $of)
    {
        parent::__construct(array_values($models instanceof Traversable ? iterator_to_array($models) : $models));
        $this->model = $of;
    }

    /**
     * Get primary keys of all models in the collection.
     *
     * @return array<int|string> Array of primary key values.
     * @throws Exception If any model has no records loaded.
     */
    public function getPrimaryKey(): array
    {
        $primaryKeys = [];
        foreach ($this->collection as $model) {
            $value = $model->getPrimaryKey();
            if (is_int($value) || is_string($value)) {
                $primaryKeys[] = $value;
            }
        }

        return $primaryKeys;
    }

    /**
     * Check if every model in the collection has clean columns.
     *
     * @param string|null $column Optional column name to check.
     * @return bool True if all models are clean.
     */
    public function isClean(?string $column = null): bool
    {
        return $this->every(fn (Model $model): bool => $model->isClean($column));
    }

    /**
     * Check if any model in the collection has dirty columns.
     *
     * @param string|null $column Optional column name to check.
     * @return bool True if at least one model is dirty.
     */
    public function isDirty(?string $column = null): bool
    {
        return !$this->isClean($column);
    }

    /**
     * Update all models in the collection with the given values using their primary keys.
     *
     * @param array<string, bool|int|string|null> $values Column values to update.
     * @return bool True if the update succeeded.
     * @throws Exception On execution failure.
     */
    public function update(array $values): bool
    {
        $tableName  = $this->model->getTableName();
        $pdo        = $this->model->getConnection();
        $primaryKey = $this->model->getPrimaryKeyName();
        $update     = new Update($tableName, $pdo);

        $update->values($values)->in($primaryKey, $this->getPrimaryKey());

        return $update->execute();
    }

    /**
     * Delete all models in the collection using their primary keys.
     *
     * @return bool True if the deletion succeeded.
     * @throws Exception On execution failure.
     */
    public function delete(): bool
    {
        $tableName  = $this->model->getTableName();
        $pdo        = $this->model->getConnection();
        $primaryKey = $this->model->getPrimaryKeyName();
        $delete     = new Delete($tableName, $pdo);

        $delete->in($primaryKey, $this->getPrimaryKey());

        return $delete->execute();
    }

    /**
     * Convert the collection to a plain array of all model columns.
     *
     * @return array<array-key, mixed> Merged array of all models' columns.
     */
    public function toArrayArray(): array
    {
        $arr = [];
        foreach ($this->collection as $model) {
            $arr = array_merge($arr, $model->toArray());
        }

        return $arr;
    }
}
