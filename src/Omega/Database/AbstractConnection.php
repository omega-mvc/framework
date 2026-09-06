<?php

declare(strict_types=1);

namespace Omega\Database;

use PDO;
use PDOException;
use PDOStatement;
use Throwable;

use function is_array;
use function is_int;
use function is_string;

abstract class AbstractConnection implements ConnectionInterface
{
    /** @var PDO Active PDO instance */
    protected PDO $pdo;

    /** @var PDOStatement Prepared PDO statement */
    private PDOStatement $statement;

    /** @var array<int, string|int|bool> Default PDO options. */
    protected array $defaultOptions = [
        PDO::ATTR_PERSISTENT => false,
        PDO::ATTR_ERRMODE    => PDO::ERRMODE_EXCEPTION,
    ];

    /**
     * Normalized database connection configuration.
     *
     * @var array{
     *     driver: string,
     *     host: string|null,
     *     database: string|null,
     *     port: int|null,
     *     charset: string|null,
     *     username: string|null,
     *     password: string|null,
     *     path: string|null,
     *     options: array<int, string|int|bool>
     * }
     */
    protected array $configs;

    /** @var string Currently prepared SQL query. */
    protected string $query;

    /** @var array<int, array{query: string, started: float, ended: float, duration: float|null}> Logs of executed queries with query, start, end, and duration. */
    protected array $logs = [];

    /**
     * @param array<string, mixed> $configs
     */
    public function __construct(array $configs)
    {
        $this->configs = $this->normalizeConfigs($configs);

        $dsn = $this->buildDsn();

        $this->pdo = $this->createPdo(
            $dsn,
            $this->configs['username'],
            $this->configs['password'],
            $this->mergeOptions($this->configs['options'])
        );
    }

    public function pdo(): PDO
    {
        return $this->pdo;
    }

    /**
     * Normalize configuration once for all drivers.
     *
     * @param array<string, mixed> $configs
     * @return array{
     *     driver: string,
     *     host: string|null,
     *     database: string|null,
     *     port: int|null,
     *     charset: string|null,
     *     username: string|null,
     *     password: string|null,
     *     path: string|null,
     *     options: array<int, string|int|bool>
     * }
     */
    protected function normalizeConfigs(array $configs): array
    {
        $driver = $configs['driver'] ?? null;
        $databaseDefaults = $configs['database_name'] ?? $configs['database'] ?? null;
        $userDefault = $configs['user'] ?? $configs['username'] ?? null;

        $options           = [];
        $configuredOptions = $configs['options'] ?? [];

        if (is_array($configuredOptions)) {
            foreach ($configuredOptions as $key => $value) {
                if (is_int($key) && (is_int($value) || is_bool($value) || is_string($value))) {
                    $options[$key] = $value;
                }
            }
        }

        return [
            'driver'   => is_string($driver) ? $driver : 'mysql',
            'host'     => is_string($configs['host'] ?? null) ? $configs['host'] : null,
            'database' => is_string($databaseDefaults) ? $databaseDefaults : null,
            'port'     => is_int($configs['port'] ?? null) ? $configs['port'] : null,
            'charset'  => is_string($configs['charset'] ?? null) ? $configs['charset'] : null,
            'username' => is_string($userDefault) ? $userDefault : null,
            'password' => is_string($configs['password'] ?? null) ? $configs['password'] : null,
            'path'     => is_string($configs['path'] ?? null) ? $configs['path'] : null,
            'options'  => $options,
        ];
    }

    /**
     * Merge driver options with defaults.
     *
     * @param array<int, string|int|bool> $options
     * @return array<int, string|int|bool>
     */
    protected function mergeOptions(array $options): array
    {
        return $options + $this->defaultOptions;
    }

    /**
     * Create PDO with retry on lost connection.
     *
     * @param array<int, string|int|bool> $options
     */
    protected function createPdo(
        string $dsn,
        ?string $username,
        ?string $password,
        array $options
    ): PDO {
        try {
            return new PDO($dsn, $username ?? '', $password ?? '', $options);
        } catch (PDOException $e) {
            if ($this->causedByLostConnection($e)) {
                return new PDO($dsn, $username ?? '', $password ?? '', $options);
            }

            throw $e;
        }
    }

    /**
     * Reconnect if the persisted connection has gone away.
     *
     * Safely rebuilds a dropped database link in a long-lived (RoadRunner)
     * worker instead of leaving a dead PDO handle cached for the process
     * lifetime. It is invoked at the `beginTransaction()` boundary only:
     * running the `SELECT 1` ping from `query()`/`execute()` collides with
     * the open result sets of nested subqueries (SQLSTATE[HY000] 2014).
     */
    protected function reconnectIfLost(): void
    {
        try {
            $result = $this->pdo->query('SELECT 1');
            if ($result instanceof PDOStatement) {
                $result->closeCursor();
            }
        } catch (Throwable $e) {
            if (!$this->causedByLostConnection($e)) {
                throw $e;
            }

            $this->pdo = $this->createPdo(
                $this->buildDsn(),
                $this->configs['username'],
                $this->configs['password'],
                $this->mergeOptions($this->configs['options'])
            );
        }
    }

    /**
     * Centralized lost connection detection.
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

        return array_any($errors, fn(string $error): bool => false !== stripos($message, $error));
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
     * Each driver must provide its own DSN.
     */
    abstract protected function buildDsn(): string;

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
    public function bind(string|int $param, mixed $value, int|null $type = null): self
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

        $rows   = $this->statement->fetchAll(PDO::FETCH_ASSOC);
        $result = [];

        foreach ($rows as $row) {
            $normalized = [];

            if (is_array($row)) {
                foreach ($row as $key => $value) {
                    if (is_string($key)) {
                        $normalized[$key] = $value;
                    }
                }
            }

            $result[] = $normalized;
        }

        return $result;
    }

    /**
     * {@inheritdoc}
     */
    public function single(): array|false
    {
        $this->execute();

        $row = $this->statement->fetch(PDO::FETCH_ASSOC);

        if (!is_array($row)) {
            return false;
        }

        $normalized = [];

        foreach ($row as $key => $value) {
            if (is_string($key)) {
                $normalized[$key] = $value;
            }
        }

        return $normalized;
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
     *
     * @param callable(ConnectionInterface $connection): bool $callable
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
        $this->reconnectIfLost();

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
     * Determine whether a transaction is currently active on the connection.
     *
     * Used to detect dangling transactions at a request boundary so they can
     * be rolled back before the persistent worker reuses the connection.
     *
     * @return bool True if a transaction is currently in progress.
     */
    public function inTransaction(): bool
    {
        return $this->pdo->inTransaction();
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
     *
     * @return array<int, array{query: string, started: float, ended: float, duration: float|null}>
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
