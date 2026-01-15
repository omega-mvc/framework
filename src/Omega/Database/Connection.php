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

/** @noinspection PhpUnnecessaryCurlyVarSyntaxInspection */

declare(strict_types=1);

namespace Omega\Database;

use Exception;
use Omega\Database\Exceptions\InvalidConfigurationException;
use PDO;
use PDOException;
use PDOStatement;
use Throwable;

use function array_any;
use function array_filter;
use function array_key_exists;
use function call_user_func;
use function implode;
use function is_bool;
use function is_int;
use function is_null;
use function microtime;
use function realpath;
use function round;
use function str_contains;
use function stripos;

/**
 * PDO-based database connection factory and execution context.
 *
 * This class is responsible for:
 * - Normalizing and validating database configuration
 * - Building driver-specific DSN strings
 * - Creating and re-creating PDO connections
 * - Preparing and executing SQL statements
 * - Managing transactions
 * - Collecting query execution logs
 *
 * Despite its name, this class acts as a connection factory and
 * proxy rather than a raw connection object.
 *
 * @category  Omega
 * @package   Database
 * @link      https://omega-mvc.github.io
 * @author    Adriano Giovannini <agisoftt@gmail.com>
 * @copyright Copyright (c) 2025 - 2026 Adriano Giovannini (https://omega-mvc.github.io)
 * @license   https://www.gnu.org/licenses/gpl-3.0-standalone.html     GPL V3.0+
 * @version   2.0.0
 */
class Connection implements ConnectionInterface
{
    /** @var PDO Active PDO instance */
    protected PDO $pdo;

    /** @var PDOStatement Prepared PDO statement */
    private PDOStatement $statement;

    /** @var array<int, string|int|bool> Default PDO options. */
    protected array $option = [
        PDO::ATTR_PERSISTENT => true,
        PDO::ATTR_ERRMODE    => PDO::ERRMODE_EXCEPTION,
    ];

    /**
     * Normalized database connection configuration.
     *
     * @var array{
     *     driver: string,
     *     host: ?string,
     *     database: ?string,
     *     port: ?int,
     *     charset: ?string,
     *     username: ?string,
     *     password: ?string,
     *     options: array<int, string|int|bool>
     * }
     * @noinspection PhpGetterAndSetterCanBeReplacedWithPropertyHooksInspection
     */
    protected array $configs;

    /** @var string Currently prepared SQL query. */
    protected string $query;

    /** @var array<int, array<string, mixed>> Logs of executed queries with query, start, end, and duration. */
    protected array $logs = [];

    /**
     * Create a new database connection instance.
     *
     * This constructor normalizes configuration, builds the DSN,
     * and creates the underlying PDO connection.
     *
     * @param array<string, string|int|array<int, string|int|bool>|null> $configs
     *        Raw database configuration values.
     * @throws PDOException When connection creation fails.
     */
    public function __construct(array $configs)
    {
        $dsnConfig = $this->setConfigs($configs);
        $dsn       = $this->getDsn($dsnConfig);
        $this->pdo = $this->createConnection($dsn, $dsnConfig, $dsnConfig['options']);
    }

    /**
     * Return the current connection instance.
     *
     * This method exists for backward compatibility and does not
     * implement a real singleton pattern.
     *
     * @return self
     */
    public function getInstance(): self
    {
        return $this;
    }

    /**
     * @deprecated Use createConnection() instead.
     *
     * @param string $dsn The data source name.
     * @param string $user Database username.
     * @param string $pass Database password.
     * @return self
     * @throws Exception When PDO connection fails.
     */
    protected function useDsn(string $dsn, string $user, string $pass): self
    {
        $option = [
            PDO::ATTR_PERSISTENT => true,
            PDO::ATTR_ERRMODE    => PDO::ERRMODE_EXCEPTION,
        ];

        try {
            $this->pdo = new PDO($dsn, $user, $pass, $option);
        } catch (PDOException $e) {
            throw new Exception($e->getMessage());
        }

        return $this;
    }

    /**
     * Create a PDO connection using a DSN string.
     *
     * @param string                      $dsn     Data source name.
     * @param array<string, string>       $configs Normalized configuration values.
     * @param array<int, string|int|bool> $options PDO options.
     * @return PDO The PDO connection instance.
     * @throws PDOException If connection cannot be established.
     */
    protected function createConnection(string $dsn, array $configs, array $options): PDO
    {
        [$username, $password] = [
            $configs['username'] ?? null, $configs['password'] ?? null,
        ];

        try {
            return new PDO($dsn, $username, $password, $options);
        } catch (PDOException $e) {
            if (true === $this->causedByLostConnection($e)) {
                return new PDO($dsn, $username, $password, $options);
            }

            throw $e;
        }
    }

    /**
     * Determine whether an exception was caused by a lost database connection.
     *
     * @param Throwable $e The exception to check.
     * @return bool True if the exception indicates a lost connection.
     */
    protected function causedByLostConnection(Throwable $e): bool
    {
        $errors = [
            // MySQL/MariaDB
            'child connection forced to terminate due to client_idle_limit',
            'SQLSTATE[HY000] [2002] Operation in progress',
            'Error writing data to the connection',
            'running with the --read-only option',
            'Server is in script upgrade mode',
            'Packets out of order. Expected',
            'Resource deadlock avoided',
            'is dead or not enabled',
            'server has gone away',
            'Error while sending',
            'query_wait_timeout',
            'Lost connection',
            // PostgresSQL
            'could not connect to server: Connection refused',
            'server closed the connection unexpectedly',
            'connection is no longer usable',
            'no connection to the server',
            // SQLite
            'No such file or directory',
            'Transaction() on null',
            // SSL
            'SQLSTATE[HY000]: General error: 7 SSL SYSCALL error',
            'SSL connection has been closed unexpectedly',
            'decryption failed or bad record mac',
            'SSL: Connection timed out',
            'SSL: Operation timed out',
            'SSL: Broken pipe',
            // Network error
            'The connection is broken and recovery is not possible',
            'Physical connection is not usable',
            'Communication link failure',
            'No route to host',
            'reset by peer',
            // Network timeout
            'Connection timed out',
            'Login timeout expired',
            // General error
            'SQLSTATE[HY000] [2002] Connection refused',
            'SQLSTATE[08S01]: Communication link failure',
            'php_network_getaddresses: getaddrinfo failed',
            'The client was disconnected by the server because of inactivity',
            'Temporary failure in name resolution',
            'could not translate host name',
        ];

        $message = $e->getMessage();

        return array_any($errors, fn($error) => false !== stripos($message, $error));
    }

    /**
     * Create a new Connection instance from configuration.
     *
     * @param array<string, string> $configs Raw database configuration.
     * @return Connection New connection instance.
     */
    public static function conn(array $configs): Connection
    {
        return new self($configs);
    }

    /**
     * Retrieve the normalized connection configuration.
     *
     * @return array{
     *     driver: string,
     *     host: ?string,
     *     database: ?string,
     *     port: ?int,
     *     charset: ?string,
     *     username: ?string,
     *     password: ?string,
     *     options: array<int, string|int|bool>
     * }
     */
    public function configs(): array
    {
        return $this->configs;
    }

    /**
     * Normalize raw database configuration values.
     *
     * @param array<string, string|int|array<int, int|bool>|null> $configs Raw configuration values.
     * @return array{
     *     driver: string,
     *     host: ?string,
     *     database: ?string,
     *     port: ?int,
     *     charset: ?string,
     *     username: ?string,
     *     password: ?string,
     *     options: array<int, string|int|bool>
     * }
     */
    protected function setConfigs(array $configs): array
    {
        return $this->configs = [
            'driver'   => $configs['driver'] ?? 'mysql',
            'host'     => $configs['host'] ?? null,
            'database' => $configs['database_name'] ?? $configs['database'] ?? null,
            'port'     => $configs['port'] ?? null,
            'charset'  => $configs['charset'] ?? null,
            'username' => $configs['user'] ?? $configs['username'] ?? null,
            'password' => $configs['password'] ?? null,
            'options'  => $configs['options'] ?? $this->option,
        ];
    }

    /**
     * Build a driver-specific DSN string.
     *
     * @param array{
     *     host: string,
     *     driver: 'mysql'|'mariadb'|'pgsql'|'sqlite',
     *     database: ?string,
     *     port: ?int,
     *     charset: ?string
     * } $configs Normalized configuration values.
     * @return string Driver-specific DSN string.
     */
    public function getDsn(array $configs): string
    {
        return match ($configs['driver']) {
            'mysql', 'mariadb' => $this->makeMysqlDsn($configs),
            'pgsql'  => $this->makePgsqlDsn($configs),
            'sqlite' => $this->makeSqliteDsn($configs),
        };
    }

    /**
     * Build a MySQL or MariaDB DSN string.
     *
     * @param array<string, string|int|array<int, string|bool>> $config Normalized config values.
     * @return string DSN string.
     * @throws InvalidConfigurationException When required values are missing.
     */
    private function makeMysqlDsn(array $config): string
    {
        // required
        if (false === array_key_exists('host', $config)) {
            throw new InvalidConfigurationException('mysql driver require `host`.');
        }

        $dsn['host']    = "host={$config['host']}";
        $dsn['dbname']  = isset($config['database']) ? "dbname={$config['database']}" : '';
        $dsn['port']    = 'port=' . ($config['port'] ?? 3306);
        $dsn['charset'] = 'charset=' . ($config['charset'] ?? 'utf8mb4');

        $build = implode(';', array_filter($dsn, fn (string $item): bool => '' !== $item));

        return "mysql:{$build}";
    }

    /**
     * Build a PostgreSQL DSN string.
     *
     * @param array<string, string|int|array<int, string|bool>> $config Normalized config values.
     * @return string DSN string.
     * @throws InvalidConfigurationException When required values are missing.
     */
    private function makePgsqlDsn(array $config): string
    {
        // required
        if (false === array_key_exists('host', $config)) {
            throw new InvalidConfigurationException('pgsql driver require `host` and `dbname`.');
        }

        $dsn['host']     = "host={$config['host']}";
        $dsn['dbname']   = isset($config['database']) ? "dbname={$config['database']}" : '';
        $dsn['port']     = 'port=' . ($config['port'] ?? 5432);
        $dsn['encoding'] = 'client_encoding=' . ($config['charset'] ?? 'utf8');

        $build = implode(';', array_filter($dsn, fn (string $item): bool => '' !== $item));

        return "pgsql:{$build}";
    }

    /**
     * Build a SQLite DSN string.
     *
     * @param array<string, string|int|array<int, string|bool>> $config Normalized config values.
     * @return string DSN string.
     * @throws InvalidConfigurationException When the database path is invalid.
     */
    private function makeSqliteDsn(array $config): string
    {
        if (false === array_key_exists('database', $config)) {
            throw new InvalidConfigurationException('sqlite driver require `database`.');
        }
        $path = $config['database'];

        if (
            $path === ':memory:'
            || str_contains($path, '?mode=memory')
            || str_contains($path, '&mode=memory')
        ) {
            return "sqlite:{$path}";
        }

        if (false === ($path = realpath($path))) {
            throw new InvalidConfigurationException('sqlite driver require `database` with absolute path.');
        }

        return "sqlite:{$path}";
    }

    /**
     * Add a query execution log entry.
     *
     * @param string $query The executed SQL query.
     * @param float $startTime Query start timestamp in seconds.
     * @param float $endTime Query end timestamp in seconds.
     * @return void
     */
    protected function addLog(string $query, float $startTime, float $endTime): void
    {
        $this->logs[] = [
            'query'    => $query,
            'started'  => $startTime,
            'ended'    => $endTime,
            'duration' => null,
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function query(string $query): self
    {
        $this->statement = $this->pdo->prepare($this->query = $query);

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function bind(string|int|bool|null $param, mixed $value, string|int|bool|null $type = null): self
    {
        if (is_null($type)) {
            $type = match (true) {
                is_int($value)  => PDO::PARAM_INT,
                is_bool($value) => PDO::PARAM_BOOL,
                is_null($value) => PDO::PARAM_NULL,
                default         => PDO::PARAM_STR,
            };
        }
        $this->statement->bindValue($param, $value, $type);

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function execute(): bool
    {
        $start    = microtime(true);
        $execute  = $this->statement->execute();

        $this->addLog($this->query, $start, microtime(true));

        return $execute;
    }

    /**
     * {@inheritdoc}
     */
    public function resultset(): array|false
    {
        $this->execute();

        return $this->statement->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * {@inheritdoc}
     */
    public function single(): mixed
    {
        $this->execute();

        return $this->statement->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * {@inheritdoc}
     */
    public function rowCount(): int
    {
        return $this->statement->rowCount();
    }

    /**
     * {@inheritdoc}
     */
    public function lastInsertId(): string|false
    {
        return $this->pdo->lastInsertId();
    }

    /**
     * {@inheritdoc}
     */
    public function transaction(callable $callable): bool
    {
        try {
            if (false === $this->beginTransaction()) {
                return false;
            }

            $return_call =  call_user_func($callable, $this);
            if (true !== $return_call) {
                $this->cancelTransaction();

                return false;
            }

            return $this->endTransaction();
        } catch (Throwable) {
            $this->cancelTransaction();

            return false;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function beginTransaction(): bool
    {
        return $this->pdo->beginTransaction();
    }

    /**
     * {@inheritdoc}
     */
    public function endTransaction(): bool
    {
        return $this->pdo->commit();
    }

    /**
     * {@inheritdoc}
     */
    public function cancelTransaction(): bool
    {
        return $this->pdo->rollBack();
    }

    /**
     * {@inheritdoc}
     */
    public function flushLogs(): void
    {
        $this->logs = [];
    }

    /**
     * {@inheritdoc}
     */
    public function getLogs(): array
    {
        foreach ($this->logs as &$log) {
            $log['duration'] ??= round(($log['ended'] - $log['started']) * 1000, 2);
        }

        unset($log);

        return $this->logs;
    }
}
