<?php

declare(strict_types=1);

namespace Tests\Database\Pdo;

use Omega\Database\ConnectionFactory;
use Omega\Database\Exceptions\InvalidConfigurationException;
use Omega\Database\MariadbConnection;
use Omega\Database\MysqlConnection;
use Omega\Database\PgsqlConnection;
use Omega\Database\SqliteConnection;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

#[CoversClass(MysqlConnection::class)]
#[CoversClass(MariadbConnection::class)]
#[CoversClass(PgsqlConnection::class)]
#[CoversClass(SqliteConnection::class)]
#[CoversClass(ConnectionFactory::class)]
#[CoversClass(InvalidConfigurationException::class)]
final class ConfigsTest extends TestCase
{
    /**
     * Build the DSN for a driver class without opening a real connection.
     *
     * Driver connections connect eagerly in their constructor, so the
     * protected `buildDsn()` implementation is exercised via reflection on
     * an instance created without invoking the constructor.
     */
    private function buildDsn(string $connectionClass, array $config): string
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

        return $buildDsn->invoke($connection);
    }

    /**
     * @test
     *
     * @group database
     */
    public function testItNormalizesLegacyConfigKeys()
    {
        $reflection = new ReflectionClass(MysqlConnection::class);
        $connection = $reflection->newInstanceWithoutConstructor();

        $normalize = $reflection->getMethod('normalizeConfigs');
        $normalize->setAccessible(true);

        $configs = $normalize->invoke($connection, [
            'driver'        => 'mysql',
            'host'          => 'localhost',
            'database_name' => 'db_from_name',
            'user'          => 'legacy_user',
            'options'       => ['foo' => 1],
        ]);

        $this->assertSame('mysql', $configs['driver']);
        $this->assertSame('db_from_name', $configs['database']);
        $this->assertSame('legacy_user', $configs['username']);
        $this->assertSame(['foo' => 1], $configs['options']);
    }

    /**
     * @test
     *
     * @group database
     */
    public function testItCanCreateMysqlDsnWithAllParameters()
    {
        $config = [
            'driver'   => 'mysql',
            'host'     => '127.0.0.1',
            'database' => 'test_db',
            'port'     => 3306,
            'charset'  => 'utf8mb4',
        ];

        $this->assertEquals(
            'mysql:host=127.0.0.1;port=3306;dbname=test_db;charset=utf8mb4',
            $this->buildDsn(MysqlConnection::class, $config)
        );
    }

    /**
     * @test
     *
     * @group database
     */
    public function testItCanCreateMysqlDsnWithMinimalParameters()
    {
        $config = [
            'driver'   => 'mysql',
            'host'     => 'localhost',
            'database' => 'test_db',
        ];

        $this->assertEquals(
            'mysql:host=localhost;port=3306;dbname=test_db;charset=utf8mb4',
            $this->buildDsn(MysqlConnection::class, $config)
        );
    }

    /**
     * @test
     *
     * @group database
     */
    public function testItCanCreateMysqlDsnWithCustomPort()
    {
        $config = [
            'driver'   => 'mysql',
            'host'     => '192.168.1.100',
            'database' => 'custom_db',
            'port'     => 3307,
        ];

        $this->assertEquals(
            'mysql:host=192.168.1.100;port=3307;dbname=custom_db;charset=utf8mb4',
            $this->buildDsn(MysqlConnection::class, $config)
        );
    }

    /**
     * @test
     *
     * @group database
     */
    public function testItCanCreateMysqlDsnWithCustomCharset()
    {
        $config = [
            'driver'   => 'mysql',
            'host'     => 'db.example.com',
            'database' => 'legacy_db',
            'charset'  => 'latin1',
        ];

        $this->assertEquals(
            'mysql:host=db.example.com;port=3306;dbname=legacy_db;charset=latin1',
            $this->buildDsn(MysqlConnection::class, $config)
        );
    }

    /**
     * @test
     *
     * @group database
     */
    public function testItCanCreateMysqlDsnWithZeroPort()
    {
        $config = [
            'driver'   => 'mysql',
            'host'     => 'localhost',
            'database' => 'test_db',
            'port'     => 0,
        ];

        $this->assertEquals(
            'mysql:host=localhost;port=0;dbname=test_db;charset=utf8mb4',
            $this->buildDsn(MysqlConnection::class, $config)
        );
    }

    /**
     * @test
     *
     * @group database
     */
    public function testItCanCreateMysqlDsnThrowsExceptionWhenHostMissing()
    {
        $config = [
            'driver'   => 'mysql',
            'database' => 'test_db',
        ];

        $this->expectException(InvalidConfigurationException::class);
        $this->expectExceptionMessage('MySQL requires host and database.');
        $this->buildDsn(MysqlConnection::class, $config);
    }

    /**
     * @test
     *
     * @group database
     */
    public function testItCanCreateMysqlDsnThrowsExceptionWhenDatabaseMissing()
    {
        $config = [
            'driver' => 'mysql',
            'host'   => 'localhost',
        ];

        $this->expectException(InvalidConfigurationException::class);
        $this->expectExceptionMessage('MySQL requires host and database.');
        $this->buildDsn(MysqlConnection::class, $config);
    }

    // MariaDB Driver Tests (shares same logic as MySQL)

    /**
     * @test
     *
     * @group database
     */
    public function testItCanCreateMariadbDsnWithAllParameters()
    {
        $config = [
            'driver'   => 'mariadb',
            'host'     => 'mariadb.example.com',
            'database' => 'maria_db',
            'port'     => 3306,
            'charset'  => 'utf8',
        ];

        $this->assertEquals(
            'mysql:host=mariadb.example.com;port=3306;dbname=maria_db;charset=utf8',
            $this->buildDsn(MariadbConnection::class, $config)
        );
    }

    /**
     * @test
     *
     * @group database
     */
    public function testItCanCreateMariadbDsnThrowsExceptionWhenHostMissing()
    {
        $config = [
            'driver'   => 'mariadb',
            'database' => 'test_db',
        ];

        $this->expectException(InvalidConfigurationException::class);
        $this->expectExceptionMessage('MariaDB requires host and database.');
        $this->buildDsn(MariadbConnection::class, $config);
    }

    // PostgreSQL Driver Tests

    /**
     * @test
     *
     * @group database
     */
    public function testItCanCreatePgsqlDsnWithAllParameters()
    {
        $config = [
            'driver'   => 'pgsql',
            'host'     => 'localhost',
            'database' => 'postgres_db',
            'port'     => 5432,
            'charset'  => 'utf8',
        ];

        $this->assertEquals(
            "pgsql:host=localhost;port=5432;dbname=postgres_db;options='--client_encoding=utf8'",
            $this->buildDsn(PgsqlConnection::class, $config)
        );
    }

    /**
     * @test
     *
     * @group database
     */
    public function testItCanCreatePgsqlDsnWithMinimalParameters()
    {
        $config = [
            'driver'   => 'pgsql',
            'host'     => '127.0.0.1',
            'database' => 'test_db',
        ];

        $this->assertEquals(
            "pgsql:host=127.0.0.1;port=5432;dbname=test_db;options='--client_encoding=utf8'",
            $this->buildDsn(PgsqlConnection::class, $config)
        );
    }

    /**
     * @test
     *
     * @group database
     */
    public function testItCanCreatePgsqlDsnWithCustomPort()
    {
        $config = [
            'driver'   => 'pgsql',
            'host'     => 'pg.server.com',
            'database' => 'production_db',
            'port'     => 5433,
        ];

        $this->assertEquals(
            "pgsql:host=pg.server.com;port=5433;dbname=production_db;options='--client_encoding=utf8'",
            $this->buildDsn(PgsqlConnection::class, $config)
        );
    }

    /**
     * @test
     *
     * @group database
     */
    public function testItCanCreatePgsqlDsnWithCustomEncoding()
    {
        $config = [
            'driver'   => 'pgsql',
            'host'     => 'postgres.example.com',
            'database' => 'international_db',
            'charset'  => 'latin1',
        ];

        $this->assertEquals(
            "pgsql:host=postgres.example.com;port=5432;dbname=international_db;options='--client_encoding=latin1'",
            $this->buildDsn(PgsqlConnection::class, $config)
        );
    }

    /**
     * @test
     *
     * @group database
     */
    public function testItCanCreatePgsqlDsnWithZeroPort()
    {
        $config = [
            'driver'   => 'pgsql',
            'host'     => 'localhost',
            'database' => 'test_db',
            'port'     => 0,
        ];

        $this->assertEquals(
            "pgsql:host=localhost;port=0;dbname=test_db;options='--client_encoding=utf8'",
            $this->buildDsn(PgsqlConnection::class, $config)
        );
    }

    /**
     * @test
     *
     * @group database
     */
    public function testItCanCreatePgsqlDsnThrowsExceptionWhenHostMissing()
    {
        $config = [
            'driver'   => 'pgsql',
            'database' => 'test_db',
            'port'     => 5432,
        ];

        $this->expectException(InvalidConfigurationException::class);
        $this->expectExceptionMessage('PostgreSQL requires host and database.');
        $this->buildDsn(PgsqlConnection::class, $config);
    }

    /**
     * @test
     *
     * @group database
     */
    public function testItCanCreatePgsqlDsnThrowsExceptionWhenDatabaseMissing()
    {
        $config = [
            'driver' => 'pgsql',
            'host'   => 'localhost',
            'port'   => 5432,
        ];

        $this->expectException(InvalidConfigurationException::class);
        $this->expectExceptionMessage('PostgreSQL requires host and database.');
        $this->buildDsn(PgsqlConnection::class, $config);
    }

    // SQLite Driver Tests

    /**
     * @test
     *
     * @group database
     */
    public function testItCanCreateSqliteDsnWithMemoryDatabase()
    {
        $config = [
            'driver'   => 'sqlite',
            'database' => ':memory:',
        ];

        $this->assertEquals(
            'sqlite::memory:',
            $this->buildDsn(SqliteConnection::class, $config)
        );
    }

    /**
     * @test
     *
     * @group database
     */
    public function testItCanCreateSqliteDsnWithmodeMemoryQueryParameter()
    {
        $config = [
            'driver'   => 'sqlite',
            'database' => '/path/to/db.sqlite?mode=memory',
        ];

        $this->assertEquals(
            'sqlite:/path/to/db.sqlite?mode=memory',
            $this->buildDsn(SqliteConnection::class, $config)
        );
    }

    /**
     * @test
     *
     * @group database
     */
    public function testItCanCreateSqliteDsnWithCacheSharedAndmodeMemoryQueryParameter()
    {
        $config = [
            'driver'   => 'sqlite',
            'database' => '/path/to/db.sqlite?cache=shared&mode=memory',
        ];

        $this->assertEquals(
            'sqlite:/path/to/db.sqlite?cache=shared&mode=memory',
            $this->buildDsn(SqliteConnection::class, $config)
        );
    }

    /**
     * @test
     *
     * @group database
     */
    public function testItCanCreateSqliteDsnThrowsExceptionWhenDatabaseMissing()
    {
        $config = [
            'driver' => 'sqlite',
            'host'   => 'localhost',
        ];

        $this->expectException(InvalidConfigurationException::class);
        $this->expectExceptionMessage('SQLite requires path.');
        $this->buildDsn(SqliteConnection::class, $config);
    }

    /**
     * @test
     *
     * @group database
     */
    public function testItCanCreateSqliteDsnThrowsExceptionForInvalidPath()
    {
        $config = [
            'driver'   => 'sqlite',
            'database' => '/non/existent/path/database.sqlite',
        ];

        $this->expectException(InvalidConfigurationException::class);
        $this->expectExceptionMessage('SQLite requires valid file path.');
        $this->buildDsn(SqliteConnection::class, $config);
    }

    // Edge Cases and Additional Coverage

    /**
     * @test
     *
     * @group database
     */
    public function testItCannotCreateConnectionWithUnsupportedDriver()
    {
        $config = [
            'driver' => 'oracle',
            'host'   => 'oracle.server.com',
        ];

        $this->expectException(\Error::class);
        $this->expectExceptionMessage('OracleConnection');
        ConnectionFactory::make($config);
    }
}
