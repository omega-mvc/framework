<?php

declare(strict_types=1);

namespace Omega\Database;

use Omega\Database\Exceptions\InvalidConfigurationException;

use function get_debug_type;
use function is_string;
use function sprintf;
use function strtolower;

final class ConnectionFactory
{
    /**
     * Supported database drivers mapped to their connection class.
     *
     * @var array<string, class-string<ConnectionInterface>>
     */
    private const array DRIVERS = [
        'mysql'   => MysqlConnection::class,
        'mariadb' => MariadbConnection::class,
        'pgsql'   => PgsqlConnection::class,
        'sqlite'  => SqliteConnection::class,
    ];

    /**
     * Instantiate a connection for the given database configuration.
     *
     * @param array<string, mixed> $config Database connection configuration.
     */
    public static function make(array $config): ConnectionInterface
    {
        $driver = $config['driver'] ?? null;

        if (!is_string($driver) || !isset(self::DRIVERS[strtolower($driver)])) {
            $driverLabel = is_string($driver) ? $driver : get_debug_type($driver);

            throw new InvalidConfigurationException(
                sprintf('Unsupported database driver [%s].', $driverLabel)
            );
        }

        $class = self::DRIVERS[strtolower($driver)];

        return new $class($config);
    }
}
