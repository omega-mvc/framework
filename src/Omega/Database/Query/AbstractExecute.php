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

namespace Omega\Database\Query;

/**
 * Abstract base class for executable queries.
 *
 * Extends AbstractQuery and provides functionality to execute
 * the built SQL query using the PDO connection and bound parameters.
 *
 * @category   Omega
 * @package    Database
 * @subpackage Query
 * @link       https://omega-mvc.github.io
 * @author     Adriano Giovannini <agisoftt@gmail.com>
 * @copyright  Copyright (c) 2025 - 2026 Adriano Giovannini (https://omega-mvc.github.io)
 * @license    https://www.gnu.org/licenses/gpl-3.0-standalone.html     GPL V3.0+
 * @version    2.0.0
 */
abstract class AbstractExecute extends AbstractQuery
{
    /**
     * Execute the built SQL query with bound parameters.
     *
     * This method calls the builder to construct the query string,
     * binds all parameters that are not already bound, executes the
     * query, and returns whether any rows were affected.
     *
     * @return bool True if one or more rows were affected, false otherwise.
     */
    public function execute(): bool
    {
        $this->builder();

        if ($this->query != null) {
            $this->pdo->query($this->query);

            foreach ($this->binds as $bind) {
                if (!$bind->hasBind()) {
                    $this->pdo->bind($bind->getBind(), $bind->getValue());
                }
            }

            $this->pdo->execute();

            return $this->pdo->rowCount() > 0;
        }

        return false;
    }
}
