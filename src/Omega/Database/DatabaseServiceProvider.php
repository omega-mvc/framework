<?php

declare(strict_types=1);

namespace Omega\Database;

use Omega\Config\ConfigRepository;
use Omega\Container\AbstractServiceProvider;
use Omega\Container\Exceptions\BindingResolutionException;
use Omega\Container\Exceptions\CircularAliasException;
use Omega\Container\Exceptions\EntryNotFoundException;
use Omega\Database\Exceptions\InvalidConfigurationException;
use Omega\Database\Query\Query;
use Omega\Database\Schema\Schema;
use Omega\Database\Schema\SchemaConnection;
use Omega\Database\Schema\SchemaConnectionInterface;
use Psr\Container\ContainerExceptionInterface;
use ReflectionException;

use function is_array;
use function is_string;
use function sprintf;

class DatabaseServiceProvider extends AbstractServiceProvider
{
    /**
     * @return void
     * @throws InvalidConfigurationException Thrown when the database configuration is invalid.
     * @throws BindingResolutionException Thrown when resolving a binding fails.
     * @throws CircularAliasException Thrown when alias resolution loops recursively.
     * @throws ContainerExceptionInterface Thrown on general container errors, e.g., service not retrievable.
     * @throws EntryNotFoundException Thrown when no entry exists for the identifier.
     * @throws ReflectionException Thrown when the requested class or interface cannot be reflected.
     */
    public function boot(): void
    {
        $configs = $this->app->get('config');

        if (!$configs instanceof ConfigRepository) {
            throw new InvalidConfigurationException('Database configuration not found in the application config.');
        }

        $default         = $configs['db.default'] ?? null;
        $rawConnections  = $configs['db.connections'] ?? [];

        if (!is_string($default) || !is_array($rawConnections) || !isset($rawConnections[$default])) {
            throw new InvalidConfigurationException(
                sprintf('Database connection [%s] not configured.', is_string($default) ? $default : 'unknown')
            );
        }

        $connections = [];
        foreach ($rawConnections as $name => $connection) {
            if (!is_string($name) || !is_array($connection)) {
                continue;
            }

            $normalized = [];
            foreach ($connection as $key => $value) {
                if (is_string($key)) {
                    $normalized[$key] = $value;
                }
            }

            $connections[$name] = $normalized;
        }

        $dsn = $connections[$default] ?? null;

        if (!is_array($dsn)) {
            throw new InvalidConfigurationException(
                sprintf('Database connection [%s] is not an array.', $default)
            );
        }

        $databaseName = $this->resolveDatabaseName($dsn);

        $this->app->set('dsn.default', $default);
        $this->app->set('dsn.connections', $connections);
        $this->app->set('dsn.sql', $dsn);

        $this->app->set(
            'database',
            fn (): ConnectionInterface => ConnectionFactory::make($dsn)
        );

        $this->app->set(
            SchemaConnection::class,
            fn (): SchemaConnection => new SchemaConnection($dsn)
        );

        $this->app->set(
            'Query',
            function (): Query {
                $database = $this->app->get('database');

                if (!$database instanceof ConnectionInterface) {
                    throw new InvalidConfigurationException(
                        'Database service [database] is not a ConnectionInterface.'
                    );
                }

                return new Query($database);
            }
        );

        $this->app->set(
            'Schema',
            function () use ($databaseName): Schema {
                $schemaConnection = $this->app->get(SchemaConnection::class);

                if (!$schemaConnection instanceof SchemaConnectionInterface) {
                    throw new InvalidConfigurationException(
                        sprintf('Database service [%s] is not a SchemaConnectionInterface.', SchemaConnection::class)
                    );
                }

                return new Schema($schemaConnection, $databaseName);
            }
        );

        $this->app->set(
            DatabaseManager::class,
            function () use ($connections): DatabaseManager {
                $database = $this->app->get('database');

                if (!$database instanceof ConnectionInterface) {
                    throw new InvalidConfigurationException(
                        'Database service [database] is not a ConnectionInterface.'
                    );
                }

                return (new DatabaseManager($connections))->setDefaultConnection($database);
            }
        );
    }

    /**
     * Resolve the database name from the connection configuration.
     *
     * The schema builder requires a concrete database name. The configuration
     * may provide it through `database`, `database_name`, or the SQLite `path`.
     *
     * @param array<string, mixed> $dsn Database connection configuration.
     */
    private function resolveDatabaseName(array $dsn): string
    {
        $database = $dsn['database'] ?? $dsn['database_name'] ?? null;

        if (is_string($database)) {
            return $database;
        }

        $path = $dsn['path'] ?? null;

        return is_string($path) ? $path : '';
    }
}
