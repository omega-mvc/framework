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

use Omega\Database\AbstractConnection;
use Omega\Database\Exceptions\InvalidConfigurationException;
use PDOException;

use function is_string;
use function sprintf;
use function str_contains;

/**
 * Class SchemaConnection
 *
 * Extends the base Connection class to manage database schema connections.
 * Allows retrieving the database name and configuring the PDO connection based on schema configs.
 *
 * @category   Omega
 * @package    Database
 * @subpackage Schema
 * @link       https://omega-mvc.github.io
 * @author     Adriano Giovannini <agisoftt@gmail.com>
 * @copyright  Copyright (c) 2025 - 2026 Adriano Giovannini (https://omega-mvc.github.io)
 * @license   https://www.gnu.org/licenses/gpl-3.0-standalone.html     GPL V3.0+
 * @version    2.0.0
 */
class SchemaConnection extends AbstractConnection implements SchemaConnectionInterface
{
    /** @var string Name of the connected database
     * @noinspection PhpGetterAndSetterCanBeReplacedWithPropertyHooksInspection
     */
    private string $database;

    /**
     * SchemaConnection constructor.
     *
     * Initializes a PDO connection using the provided configuration array.
     *
     * @param array<string, mixed> $configs
     *        Configuration array including driver, host, database, port, charset, username, password, and options
     * @throws PDOException If the connection cannot be established
     */
    public function __construct(array $configs)
    {
        $this->configs  = $this->normalizeConfigs($configs);
        $this->database = $this->configs['database'] ?? '';
        $dsn            = $this->buildDsn();
        $this->pdo      = $this->createPdo(
            $dsn,
            $this->configs['username'],
            $this->configs['password'],
            $this->mergeOptions($this->configs['options'])
        );
    }

    /**
     * {@inhertdoc}
     */
    public function getDatabase(): string
    {
        return $this->database;
    }

    protected function buildDsn(): string
    {
        $driver = (string) $this->configs['driver'];
        $host   = $this->configs['host'];

        return match ($driver) {
            'mysql', 'mariadb' => $this->buildMysqlDsn($host),
            'pgsql'            => $this->buildPgsqlDsn($host),
            'sqlite'           => $this->buildSqliteDsn(),
            default            => throw new InvalidConfigurationException(
                sprintf('Unsupported database driver [%s].', $driver)
            ),
        };
    }

    /**
     * Build a MySQL/MariaDB DSN without a database name so that
     * database-level operations (create/drop) can be performed.
     *
     * @param string|null $host Database host.
     * @return string The MySQL DSN.
     * @throws InvalidConfigurationException When the host is missing.
     */
    private function buildMysqlDsn(?string $host): string
    {
        if (!is_string($host) || '' === $host) {
            throw new InvalidConfigurationException("{$this->configs['driver']} requires host.");
        }

        $port = $this->configs['port'] ?? 3306;
        $char = $this->configs['charset'] ?? 'utf8mb4';

        return "mysql:host={$host};port={$port};charset={$char}";
    }

    /**
     * Build a PostgreSQL DSN without a database name so that
     * database-level operations (create/drop) can be performed.
     *
     * @param string|null $host Database host.
     * @return string The PostgreSQL DSN.
     * @throws InvalidConfigurationException When the host is missing.
     */
    private function buildPgsqlDsn(?string $host): string
    {
        if (!is_string($host) || '' === $host) {
            throw new InvalidConfigurationException("{$this->configs['driver']} requires host.");
        }

        $port = $this->configs['port'] ?? 5432;

        return "pgsql:host={$host};port={$port}";
    }

    /**
     * Build an SQLite DSN from the configured path or database.
     *
     * @return string The SQLite DSN.
     * @throws InvalidConfigurationException When the path is missing or invalid.
     */
    private function buildSqliteDsn(): string
    {
        $path = $this->configs['path'] ?? null;
        if (!is_string($path) || '' === $path) {
            throw new InvalidConfigurationException('SQLite requires path.');
        }

        if (
            ':memory:' === $path
            || str_contains($path, '?mode=memory')
            || str_contains($path, '&mode=memory')
        ) {
            return "sqlite:{$path}";
        }

        if (!is_string($path = realpath($path))) {
            throw new InvalidConfigurationException('SQLite requires valid file path.');
        }

        return 'sqlite:' . $path;
    }
}
