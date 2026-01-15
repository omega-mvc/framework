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

use Omega\Database\Connection;
use PDOException;

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
 * @license    https://www.gnu.org/licenses/gpl-3.0-standalone.html     GPL V3.0+
 * @version    2.0.0
 */
class SchemaConnection extends Connection implements SchemaConnectionInterface
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
        $dsnConfig      = $this->setConfigs($configs);
        $this->database = $configs['database'] ?? $configs['database_name'];
        $dsn            = $this->getDsn($dsnConfig);
        $this->pdo      = $this->createConnection($dsn, $dsnConfig, $dsnConfig['options']);
    }

    /**
     * {@inhertdoc}
     */
    public function getDatabase(): string
    {
        return $this->database;
    }

    /**
     * Configure connection settings from input array.
     *
     * Normalizes configuration keys and fills in default values if missing.
     *
     * @param array<string, mixed> $configs Input configuration array
     * @return array<string, mixed> Normalized configuration array ready for DSN
     */
    protected function setConfigs(array $configs): array
    {
        return $this->configs = [
            'driver'   => $configs['driver'] ?? 'mysql',
            'host'     => $configs['host'] ?? null,
            'database' => null,
            'port'     => $configs['port'] ?? null,
            'charset'  => $configs['charset'] ?? null,
            'username' => $configs['user'] ?? $configs['username'] ?? null,
            'password' => $configs['password'] ?? null,
            'options'  => $configs['options'] ?? $this->option,
        ];
    }
}
