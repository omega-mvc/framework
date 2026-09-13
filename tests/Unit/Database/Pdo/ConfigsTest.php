<?php

declare(strict_types=1);

namespace Tests\Database\Pdo;

use Omega\Database\ConnectionFactory;
use Omega\Database\Exceptions\InvalidConfigurationException;
use Omega\Database\MariadbConnection;
use Omega\Database\MysqlConnection;
use Omega\Database\PgsqlConnection;
use Omega\Database\SqliteConnection;
use ReflectionClass;
use RuntimeException;

covers(MysqlConnection::class);
covers(MariadbConnection::class);
covers(PgsqlConnection::class);
covers(SqliteConnection::class);
covers(ConnectionFactory::class);
covers(InvalidConfigurationException::class);

/**
 * Build the DSN for a driver class without opening a real connection.
 *
 * Driver connections connect eagerly in their constructor, so the
 * protected `buildDsn()` implementation is exercised via reflection on
 * an instance created without invoking the constructor.
 *
 * @param  class-string          $connectionClass
 * @param  array<string, mixed>  $config
 *
 * @return string
 */
function buildDsn(string $connectionClass, array $config): string
{
    $reflection = new ReflectionClass($connectionClass);
    $connection = $reflection->newInstanceWithoutConstructor();

    $normalize = $reflection->getMethod('normalizeConfigs');
    $normalize->setAccessible(true);
    $normalized = $normalize->invoke($connection, $config);

    $configs = $reflection->getProperty('configs');
    $configs->setAccessible(true);
    $configs->setValue($connection, $normalized);

    $buildDsn = $reflection->getMethod('buildDsn');
    $buildDsn->setAccessible(true);

    $dsn = $buildDsn->invoke($connection);

    if (!is_string($dsn)) {
        throw new RuntimeException('buildDsn() must return a string DSN.');
    }

    return $dsn;
}

test('it normalizes legacy config keys', function (): void {
    $reflection = new ReflectionClass(MysqlConnection::class);
    $connection = $reflection->newInstanceWithoutConstructor();

    $normalize = $reflection->getMethod('normalizeConfigs');
    $normalize->setAccessible(true);

    $configs = $normalize->invoke($connection, [
        'driver'        => 'mysql',
        'host'          => 'localhost',
        'database_name' => 'db_from_name',
        'user'          => 'legacy_user',
        'options'       => [\PDO::ATTR_PERSISTENT => false],
    ]);

    $this->assertIsArray($configs);
    expect($configs['driver'])->toBe('mysql');
    expect($configs['database'])->toBe('db_from_name');
    expect($configs['username'])->toBe('legacy_user');
    expect($configs['options'])->toBe([\PDO::ATTR_PERSISTENT => false]);
});

test('it can create mysql dsn with all parameters', function (): void {
    $config = [
        'driver'   => 'mysql',
        'host'     => '127.0.0.1',
        'database' => 'test_db',
        'port'     => 3306,
        'charset'  => 'utf8mb4',
    ];

    expect(buildDsn(MysqlConnection::class, $config))->toEqual(
        'mysql:host=127.0.0.1;port=3306;dbname=test_db;charset=utf8mb4'
    );
});

test('it can create mysql dsn with minimal parameters', function (): void {
    $config = [
        'driver'   => 'mysql',
        'host'     => 'localhost',
        'database' => 'test_db',
    ];

    expect(buildDsn(MysqlConnection::class, $config))->toEqual(
        'mysql:host=localhost;port=3306;dbname=test_db;charset=utf8mb4'
    );
});

test('it can create mysql dsn with custom port', function (): void {
    $config = [
        'driver'   => 'mysql',
        'host'     => '192.168.1.100',
        'database' => 'custom_db',
        'port'     => 3307,
    ];

    expect(buildDsn(MysqlConnection::class, $config))->toEqual(
        'mysql:host=192.168.1.100;port=3307;dbname=custom_db;charset=utf8mb4'
    );
});

test('it can create mysql dsn with custom charset', function (): void {
    $config = [
        'driver'   => 'mysql',
        'host'     => 'db.example.com',
        'database' => 'legacy_db',
        'charset'  => 'latin1',
    ];

    expect(buildDsn(MysqlConnection::class, $config))->toEqual(
        'mysql:host=db.example.com;port=3306;dbname=legacy_db;charset=latin1'
    );
});

test('it can create mysql dsn with zero port', function (): void {
    $config = [
        'driver'   => 'mysql',
        'host'     => 'localhost',
        'database' => 'test_db',
        'port'     => 0,
    ];

    expect(buildDsn(MysqlConnection::class, $config))->toEqual(
        'mysql:host=localhost;port=0;dbname=test_db;charset=utf8mb4'
    );
});

test('it can create mysql dsn throws exception when host missing', function (): void {
    $config = [
        'driver'   => 'mysql',
        'database' => 'test_db',
    ];

    $this->expectException(InvalidConfigurationException::class);
    $this->expectExceptionMessageIsOrContains('MySQL requires host and database.');
    buildDsn(MysqlConnection::class, $config);
});

test('it can create mysql dsn throws exception when database missing', function (): void {
    $config = [
        'driver' => 'mysql',
        'host'   => 'localhost',
    ];

    $this->expectException(InvalidConfigurationException::class);
    $this->expectExceptionMessageIsOrContains('MySQL requires host and database.');
    buildDsn(MysqlConnection::class, $config);
});

// MariaDB Driver Tests (shares same logic as MySQL)

test('it can create mariadb dsn with all parameters', function (): void {
    $config = [
        'driver'   => 'mariadb',
        'host'     => 'mariadb.example.com',
        'database' => 'maria_db',
        'port'     => 3306,
        'charset'  => 'utf8',
    ];

    expect(buildDsn(MariadbConnection::class, $config))->toEqual(
        'mysql:host=mariadb.example.com;port=3306;dbname=maria_db;charset=utf8'
    );
});

test('it can create mariadb dsn throws exception when host missing', function (): void {
    $config = [
        'driver'   => 'mariadb',
        'database' => 'test_db',
    ];

    $this->expectException(InvalidConfigurationException::class);
    $this->expectExceptionMessageIsOrContains('MariaDB requires host and database.');
    buildDsn(MariadbConnection::class, $config);
});

// PostgreSQL Driver Tests

test('it can create pgsql dsn with all parameters', function (): void {
    $config = [
        'driver'   => 'pgsql',
        'host'     => 'localhost',
        'database' => 'postgres_db',
        'port'     => 5432,
        'charset'  => 'utf8',
    ];

    expect(buildDsn(PgsqlConnection::class, $config))->toEqual(
        "pgsql:host=localhost;port=5432;dbname=postgres_db;options='--client_encoding=utf8'"
    );
});

test('it can create pgsql dsn with minimal parameters', function (): void {
    $config = [
        'driver'   => 'pgsql',
        'host'     => '127.0.0.1',
        'database' => 'test_db',
    ];

    expect(buildDsn(PgsqlConnection::class, $config))->toEqual(
        "pgsql:host=127.0.0.1;port=5432;dbname=test_db;options='--client_encoding=utf8'"
    );
});

test('it can create pgsql dsn with custom port', function (): void {
    $config = [
        'driver'   => 'pgsql',
        'host'     => 'pg.server.com',
        'database' => 'production_db',
        'port'     => 5433,
    ];

    expect(buildDsn(PgsqlConnection::class, $config))->toEqual(
        "pgsql:host=pg.server.com;port=5433;dbname=production_db;options='--client_encoding=utf8'"
    );
});

test('it can create pgsql dsn with custom encoding', function (): void {
    $config = [
        'driver'   => 'pgsql',
        'host'     => 'postgres.example.com',
        'database' => 'international_db',
        'charset'  => 'latin1',
    ];

    expect(buildDsn(PgsqlConnection::class, $config))->toEqual(
        "pgsql:host=postgres.example.com;port=5432;dbname=international_db;options='--client_encoding=latin1'"
    );
});

test('it can create pgsql dsn with zero port', function (): void {
    $config = [
        'driver'   => 'pgsql',
        'host'     => 'localhost',
        'database' => 'test_db',
        'port'     => 0,
    ];

    expect(buildDsn(PgsqlConnection::class, $config))->toEqual(
        "pgsql:host=localhost;port=0;dbname=test_db;options='--client_encoding=utf8'"
    );
});

test('it can create pgsql dsn throws exception when host missing', function (): void {
    $config = [
        'driver'   => 'pgsql',
        'database' => 'test_db',
        'port'     => 5432,
    ];

    $this->expectException(InvalidConfigurationException::class);
    $this->expectExceptionMessageIsOrContains('PostgreSQL requires host and database.');
    buildDsn(PgsqlConnection::class, $config);
});

test('it can create pgsql dsn throws exception when database missing', function (): void {
    $config = [
        'driver' => 'pgsql',
        'host'   => 'localhost',
        'port'   => 5432,
    ];

    $this->expectException(InvalidConfigurationException::class);
    $this->expectExceptionMessageIsOrContains('PostgreSQL requires host and database.');
    buildDsn(PgsqlConnection::class, $config);
});

// SQLite Driver Tests

test('it can create sqlite dsn with memory database', function (): void {
    $config = [
        'driver'   => 'sqlite',
        'database' => ':memory:',
    ];

    expect(buildDsn(SqliteConnection::class, $config))->toEqual(
        'sqlite::memory:'
    );
});

test('it can create sqlite dsn with mode memory query parameter', function (): void {
    $config = [
        'driver'   => 'sqlite',
        'database' => '/path/to/db.sqlite?mode=memory',
    ];

    expect(buildDsn(SqliteConnection::class, $config))->toEqual(
        'sqlite:/path/to/db.sqlite?mode=memory'
    );
});

test('it can create sqlite dsn with cache shared and mode memory query parameter', function (): void {
    $config = [
        'driver'   => 'sqlite',
        'database' => '/path/to/db.sqlite?cache=shared&mode=memory',
    ];

    expect(buildDsn(SqliteConnection::class, $config))->toEqual(
        'sqlite:/path/to/db.sqlite?cache=shared&mode=memory'
    );
});

test('it can create sqlite dsn throws exception when database missing', function (): void {
    $config = [
        'driver' => 'sqlite',
        'host'   => 'localhost',
    ];

    $this->expectException(InvalidConfigurationException::class);
    $this->expectExceptionMessageIsOrContains('SQLite requires path.');
    buildDsn(SqliteConnection::class, $config);
});

test('it can create sqlite dsn throws exception for invalid path', function (): void {
    $config = [
        'driver'   => 'sqlite',
        'database' => '/non/existent/path/database.sqlite',
    ];

    $this->expectException(InvalidConfigurationException::class);
    $this->expectExceptionMessageIsOrContains('SQLite requires valid file path.');
    buildDsn(SqliteConnection::class, $config);
});

// Edge Cases and Additional Coverage

test('it cannot create connection with unsupported driver', function (): void {
    $config = [
        'driver' => 'oracle',
        'host'   => 'oracle.server.com',
    ];

    $this->expectException(InvalidConfigurationException::class);
    $this->expectExceptionMessageIsOrContains('Unsupported database driver [oracle].');
    ConnectionFactory::make($config);
});