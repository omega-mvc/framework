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

namespace Omega\Database\Schema;

use function array_filter;
use function implode;

/**
 * Class Query
 *
 * Abstract base class for schema queries.
 * Provides core functionality for building and executing SQL statements
 * using a SchemaConnectionInterface instance.
 *
 * @category   Omega
 * @package    Database
 * @subpackage Schema
 * @link       https://omega-mvc.github.io
 * @author     Adriano Giovannini <agisoftt@gmail.com>
 * @copyright  Copyright (c) 2025 - 2026 Adriano Giovannini (https://omega-mvc.github.io)
 * @license    https://www.gnu.org/licenses/gpl-3.0-standalone.html     GPL V3.0+
 * @version    2.0.0
 */
abstract class Query
{
    /** @var SchemaConnectionInterface PDO connection for executing queries */
    protected SchemaConnectionInterface $pdo;

    /**
     * Convert the query to string.
     *
     * @return string SQL statement
     */
    public function __toString(): string
    {
        return $this->builder();
    }

    /**
     * Build the SQL query.
     *
     * To be overridden in child classes.
     *
     * @return string SQL query string
     */
    protected function builder(): string
    {
        return '';
    }

    /**
     * Execute the built query.
     *
     * @return bool True if query executed successfully
     */
    public function execute(): bool
    {
        return $this->pdo->query($this->builder())->execute();
    }

    /**
     * Helper: join non-empty strings with a separator.
     *
     * @param string[] $array     Array of strings to join
     * @param string   $separator Separator between elements, defaults to space
     * @return string Joined string
     */
    protected function join(array $array, string $separator = ' '): string
    {
        return implode(
            $separator,
            array_filter($array, fn ($item) => $item !== '')
        );
    }
}
