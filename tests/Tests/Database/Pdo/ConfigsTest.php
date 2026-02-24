<?php

declare(strict_types=1);

namespace Tests\Database\Pdo;

use Omega\Database\Exceptions\InvalidConfigurationException;
use PHPUnit\Framework\Attributes\CoversClass;
use Tests\Database\AbstractTestDatabase;

#[CoversClass(InvalidConfigurationException::class)]
final class ConfigsTest extends AbstractTestDatabase
{
    protected function setUp(): void
    {
        $this->createConnection();
    }

    protected function tearDown(): void
    {
        $this->dropConnection();
    }

    /**
     * @test
     *
     * @group database
     */
    public function testItCanGetConfig()
    {
        $config = $this->pdo->configs();
        unset($config['options']);
        $this->assertEquals($this->env, $config);
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

        $expected = 'mysql:host=127.0.0.1;dbname=test_db;port=3306;charset=utf8mb4';
        $this->assertEquals($expected, $this->pdo->getDsn($config));
    }

    /**
     * @test
     *
     * @group database
     */
    public function testItCanCreateMysqlDsnWithMinimalParameters()
    {
        $config = [
            'driver' => 'mysql',
            'host'   => 'localhost',
        ];

        $expected = 'mysql:host=localhost;port=3306;charset=utf8mb4';
        $this->assertEquals($expected, $this->pdo->getDsn($config));
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

        $expected = 'mysql:host=192.168.1.100;dbname=custom_db;port=3307;charset=utf8mb4';
        $this->assertEquals($expected, $this->pdo->getDsn($config));
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

        $expected = 'mysql:host=db.example.com;dbname=legacy_db;port=3306;charset=latin1';
        $this->assertEquals($expected, $this->pdo->getDsn($config));
    }

    /**
     * @test
     *
     * @group database
     */
    public function testItCanCreateMysqlDsnWithoutDatabase()
    {
        $config = [
            'driver' => 'mysql',
            'host'   => 'mysql.server.com',
            'port'   => 3308,
        ];

        $expected = 'mysql:host=mysql.server.com;port=3308;charset=utf8mb4';
        $this->assertEquals($expected, $this->pdo->getDsn($config));
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
        $this->expectExceptionMessage('mysql driver require `host`.');
        $this->pdo->getDsn($config);
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

        $expected = 'mysql:host=mariadb.example.com;dbname=maria_db;port=3306;charset=utf8';
        $this->assertEquals($expected, $this->pdo->getDsn($config));
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
        $this->expectExceptionMessage('mysql driver require `host`.');
        $this->pdo->getDsn($config);
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

        $expected = 'pgsql:host=localhost;dbname=postgres_db;port=5432;client_encoding=utf8';
        $this->assertEquals($expected, $this->pdo->getDsn($config));
    }

    /**
     * @test
     *
     * @group database
     */
    public function testItCanCreatePgsqlDsnWithMinimalParameters()
    {
        $config = [
            'driver' => 'pgsql',
            'host'   => '127.0.0.1',
        ];

        $expected = 'pgsql:host=127.0.0.1;port=5432;client_encoding=utf8';
        $this->assertEquals($expected, $this->pdo->getDsn($config));
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

        $expected = 'pgsql:host=pg.server.com;dbname=production_db;port=5433;client_encoding=utf8';
        $this->assertEquals($expected, $this->pdo->getDsn($config));
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

        $expected = 'pgsql:host=postgres.example.com;dbname=international_db;port=5432;client_encoding=latin1';
        $this->assertEquals($expected, $this->pdo->getDsn($config));
    }

    /**
     * @test
     *
     * @group database
     */
    public function testItCanCreatePgsqlDsnWithoutDatabase()
    {
        $config = [
            'driver'   => 'pgsql',
            'host'     => 'pg-cluster.local',
            'port'     => 5434,
            'charset'  => 'utf8mb4',
        ];

        $expected = 'pgsql:host=pg-cluster.local;port=5434;client_encoding=utf8mb4';
        $this->assertEquals($expected, $this->pdo->getDsn($config));
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
        $this->expectExceptionMessage('pgsql driver require `host` and `dbname`.');
        $this->pdo->getDsn($config);
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

        $expected = 'sqlite::memory:';
        $this->assertEquals($expected, $this->pdo->getDsn($config));
    }

    /**
     * @test
     *
     * @group database
     */
    public function testItCanCreateSqliteDsnWithMemoryModeQuery()
    {
        $config = [
            'driver'   => 'sqlite',
            'database' => '/path/to/db.sqlite?mode=memory',
        ];

        $expected = 'sqlite:/path/to/db.sqlite?mode=memory';
        $this->assertEquals($expected, $this->pdo->getDsn($config));
    }

    /**
     * @test
     *
     * @group database
     */
    public function testItCanCreateSqliteDsnWithMemoryModeQueryAmpersand()
    {
        $config = [
            'driver'   => 'sqlite',
            'database' => '/path/to/db.sqlite?cache=shared&mode=memory',
        ];

        $expected = 'sqlite:/path/to/db.sqlite?cache=shared&mode=memory';
        $this->assertEquals($expected, $this->pdo->getDsn($config));
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
        $this->expectExceptionMessage('sqlite driver require `database`.');
        $this->pdo->getDsn($config);
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
        $this->expectExceptionMessage('sqlite driver require `database` with absolute path.');
        $this->pdo->getDsn($config);
    }

    // Edge Cases and Additional Coverage

    /**
     * @test
     *
     * @group database
     */
    public function testItCanCreateGetDsnWithUnsupportedDriver()
    {
        $config = [
            'driver' => 'oracle',
            'host'   => 'oracle.server.com',
        ];

        // This should trigger a match expression error since 'oracle' is not handled
        $this->expectException(\UnhandledMatchError::class);
        $this->pdo->getDsn($config);
    }

    /**
     * @test
     *
     * @group database
     */
    public function testItCanCreateMysqlDsnWithZeroPort()
    {
        $config = [
            'driver' => 'mysql',
            'host'   => 'localhost',
            'port'   => 0,
        ];

        $expected = 'mysql:host=localhost;port=0;charset=utf8mb4';
        $this->assertEquals($expected, $this->pdo->getDsn($config));
    }

    /**
     * @test
     *
     * @group database
     */
    public function testItCanCreatePgsqlDsnWithZeroPort()
    {
        $config = [
            'driver' => 'pgsql',
            'host'   => 'localhost',
            'port'   => 0,
        ];

        $expected = 'pgsql:host=localhost;port=0;client_encoding=utf8';
        $this->assertEquals($expected, $this->pdo->getDsn($config));
    }

    /**
     * @test
     *
     * @group database
     */
    public function testItCanCreateMysqlDsnWithNullValues()
    {
        $config = [
            'driver'   => 'mysql',
            'host'     => 'localhost',
            'database' => null,
            'port'     => null,
            'charset'  => null,
        ];

        $expected = 'mysql:host=localhost;port=3306;charset=utf8mb4';
        $this->assertEquals($expected, $this->pdo->getDsn($config));
    }

    /**
     * @test
     *
     * @group database
     */
    public function testItCanCreatePgsqlDsnWithNullValues()
    {
        $config = [
            'driver'   => 'pgsql',
            'host'     => 'localhost',
            'database' => null,
            'port'     => null,
            'charset'  => null,
        ];

        $expected = 'pgsql:host=localhost;port=5432;client_encoding=utf8';
        $this->assertEquals($expected, $this->pdo->getDsn($config));
    }
}
