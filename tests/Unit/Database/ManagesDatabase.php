<?php

/**
 * Part of Omega - Tests\Database Package.
 *
 * @link      https://omega-mvc.github.io
 * @author    Adriano Giovannini <agisoftt@gmail.com>
 * @copyright Copyright (c) 2025 - 2026 Adriano Giovannini (https://omega-mvc.github.io)
 * @license   https://www.gnu.org/licenses/gpl-3.0-standalone.html     GPL V3.0+
 * @version   2.0.0
 */

declare(strict_types=1);

namespace Tests\Database;

use Omega\Database\ConnectionInterface;
use Omega\Database\DatabaseManager;
use Omega\Database\Exceptions\InvalidConfigurationException;
use Omega\Database\Query\Insert;
use Omega\Database\Schema\Schema;
use Omega\Database\Schema\SchemaConnection;
use PDO;
use PHPUnit\Framework\TestCase;
use Throwable;

use function in_array;
use function sprintf;
use function sys_get_temp_dir;
use function tempnam;

/**
 * Trait ManagesDatabase
 *
 * Provides the shared helpers for real-engine database tests. Each Pest test
 * runs once per available driver (mysql, mariadb, pgsql, sqlite) through the
 * ``EngineMatrix::engines()`` dataset. When an engine is not installed or not
 * reachable, the corresponding execution is skipped instead of failing, so
 * the suite stays honest about which engines are actually exercised.
 *
 * @phpstan-require-extends TestCase
 *
 * @category  Tests
 * @package   Database
 * @link      https://omega-mvc.github.io
 * @author    Adriano Giovannini <agisoftt@gmail.com>
 * @copyright Copyright (c) 2025 - 2026 Adriano Giovannini (https://omega-mvc.github.io)
 * @license   https://www.gnu.org/licenses/gpl-3.0-standalone.html     GPL V3.0+
 * @version   2.0.0
 */
trait ManagesDatabase
{
    /** @var array{driver: string, host?: string, username?: string, password?: string, database: string, path?: string, port?: int, charset?: string, prefix?: string} Database connection environment variables */
    protected array $env;

    /** @var ConnectionInterface Main PDO connection instance for tests */
    protected ConnectionInterface $pdo;

    /** @var SchemaConnection Schema-level connection instance */
    protected SchemaConnection $pdoSchema;

    /** @var Schema Schema instance for database operations and teardown */
    protected Schema $schema;

    /** @var DatabaseManager Database manager instance for executing queries */
    protected DatabaseManager $db;

    /** @var string Driver currently connected (empty string when none) */
    protected string $engine = '';

    /** @var string|null Temporary SQLite file used by the current test */
    protected ?string $sqlitePath = null;

    /**
     * Data provider of the supported database engines.
     *
     * Each engine is exercised as its own dataset; engines that are not
     * installed or unreachable make the test skip instead of failing.
     *
     * @return array<string, array{string}>
     */
    public static function engineProvider(): array
    {
        return [
            'mysql'   => ['mysql'],
            'mariadb' => ['mariadb'],
            'pgsql'   => ['pgsql'],
            'sqlite'  => ['sqlite'],
        ];
    }

    /**
     * Create the database connection for the requested engine, dropping the
     * previous engine's database when switching. Connections that cannot be
     * established make the test skip instead of failing.
     *
     * @param string $engine Database engine ('mysql', 'mariadb', 'pgsql', 'sqlite')
     * @return void
     */
    protected function createConnection(string $engine): void
    {
        if ($this->engine !== '' && $this->engine !== $engine) {
            $this->dropConnection();
        }

        $this->env = $this->environmentFor($engine);

        if ('sqlite' === $engine) {
            $file = tempnam(sys_get_temp_dir(), 'omega_db_');
            if (false === $file) {
                $this->markTestSkipped('Unable to allocate a temporary SQLite file.');
            }
            $this->sqlitePath      = $file;
            $this->env['database'] = $file;
            $this->env['path']     = $file;
        } else {
            $this->assertPdoDriverAvailable($engine);
        }

        try {
            $this->pdoSchema = new SchemaConnection($this->env);
            $this->schema    = new Schema($this->pdoSchema, $this->env['database']);

            if ('sqlite' !== $engine) {
                $this->schema->create()->database($this->env['database'])->ifNotExists()->execute();
            }
        } catch (Throwable $e) {
            $this->markTestSkipped(
                sprintf('Database engine [%s] is not available: %s', $engine, $e->getMessage())
            );
        }

        $class = $this->resolveConnectionClass($engine);

        $this->pdo = new $class($this->env);

        $this->db = new DatabaseManager($this->getConfiguration());
        $this->db->setDefaultConnection($this->pdo);

        $this->engine = $engine;
    }

    /**
     * Drop the test database (non-SQLite) and remove the temporary SQLite file.
     *
     * @return void
     */
    protected function dropConnection(): void
    {
        if (null !== $this->sqlitePath) {
            @unlink($this->sqlitePath);
            $this->sqlitePath = null;
        }

        if (isset($this->schema) && $this->engine !== '' && 'sqlite' !== $this->engine) {
            $this->schema->drop()->database($this->env['database'])->ifExists()->execute();
        }

        $this->engine = '';
    }

    /**
     * Skip the execution when the requested engine does not support the
     * SQL dialect exercised by the test.
     *
     * @param string   $engine   Current engine.
     * @param string[] $engines  Engines supporting the dialect feature.
     * @param string   $feature  Feature description used in the skip message.
     * @return void
     */
    protected function requiresOneOf(string $engine, array $engines, string $feature): void
    {
        if (!in_array($engine, $engines, true)) {
            $this->markTestSkipped(
                sprintf('The SQL feature (%s) is not supported by engine [%s].', $feature, $engine)
            );
        }
    }

    /**
     * Verify that the PHP PDO driver backing the engine is installed.
     *
     * @param string $engine Database engine.
     * @return void
     */
    private function assertPdoDriverAvailable(string $engine): void
    {
        $pdoDriver = match ($engine) {
            'mysql', 'mariadb' => 'mysql',
            'pgsql'            => 'pgsql',
            default            => '',
        };

        if (!in_array($pdoDriver, PDO::getAvailableDrivers(), true)) {
            $this->markTestSkipped(
                sprintf(
                    'The PHP PDO driver [%s] is not installed, so the [%s] engine suite is skipped.',
                    $pdoDriver,
                    $engine
                )
            );
        }
    }

    /**
     * Create the "users" table in the active test database.
     *
     * @return bool True on success, false on failure
     */
    protected function createUserSchema(): bool
    {
        return $this
            ->pdo
            ->query('CREATE TABLE users (
                user      varchar(32)  NOT NULL,
                password  varchar(500) NOT NULL,
                stat      int(2)       NOT NULL,
                PRIMARY KEY (user)
            )')
            ->execute();
    }

    /**
     * Create the "users" table and seed it with a single "taylor" row.
     *
     * @return void
     */
    protected function seedDefaultUser(): void
    {
        $this->createUserSchema();
        $this->createUser([
            [
                'user'     => 'taylor',
                'password' => 'secret',
                'stat'     => 99,
            ],
        ]);
    }

    /**
     * Insert new users into the "users" table.
     *
     * @param array<int, array<string, string|int|bool|null>> $users Array of user data [{user, password, stat}]
     * @return bool True on successful insert, false otherwise
     */
    protected function createUser(array $users): bool
    {
        return (new Insert('users', $this->pdo))
            ->rows($users)
            ->execute();
    }

    /**
     * Get configuration for every supported database connection.
     *
     * Host, credentials and port can be overridden through the environment
     * (OMEGA_TEST_DB_HOST, OMEGA_TEST_DB_USERNAME, OMEGA_TEST_DB_PASSWORD
     * and OMEGA_TEST_DB_PORT). Database names are engine-specific so the
     * engines can run against the same server without colliding.
     *
     * @return array<string, array{driver: string, host: string, username: string, password: string, database: string, port: int, charset: string}>
     */
    protected function getConfiguration(): array
    {
        $host     = getenv('OMEGA_TEST_DB_HOST') ?: '127.0.0.1';
        $username = getenv('OMEGA_TEST_DB_USERNAME') ?: 'root';
        $password = getenv('OMEGA_TEST_DB_PASSWORD') ?: 'vb65ty4';
        $port     = (int) (getenv('OMEGA_TEST_DB_PORT') ?: 3306);

        return [
            'mysql' => [
                'driver'   => 'mysql',
                'host'     => $host,
                'username' => $username,
                'password' => $password,
                'database' => 'testing_db',
                'port'     => $port,
                'charset'  => 'utf8mb4',
            ],
            'mariadb' => [
                'driver'   => 'mariadb',
                'host'     => $host,
                'username' => $username,
                'password' => $password,
                'database' => 'testing_db_mariadb',
                'port'     => $port,
                'charset'  => 'utf8mb4',
            ],
            'pgsql' => [
                'driver'   => 'pgsql',
                'host'     => $host,
                'username' => $username,
                'password' => $password,
                'database' => 'testing_db_pgsql',
                'port'     => 5432,
                'charset'  => 'utf8',
            ],
            'sqlite' => [
                'driver'   => 'sqlite',
                'host'     => '',
                'username' => '',
                'password' => '',
                'database' => ':memory:',
                'port'     => 0,
                'charset'  => '',
            ],
        ];
    }

    /**
     * Resolve the configuration array for the requested engine.
     *
     * @param string $engine Database engine.
     * @return array{driver: string, host?: string, username?: string, password?: string, database: string, path?: string, port?: int, charset?: string, prefix?: string}
     */
    protected function environmentFor(string $engine): array
    {
        $configuration = $this->getConfiguration();

        return $configuration[$engine]
            ?? throw new InvalidConfigurationException(
                sprintf('Unsupported database driver [%s].', $engine)
            );
    }

    /**
     * @return class-string<ConnectionInterface>
     */
    protected function resolveConnectionClass(string $driver): string
    {
        return match ($driver) {
            'mysql'   => \Omega\Database\MysqlConnection::class,
            'mariadb' => \Omega\Database\MariadbConnection::class,
            'pgsql'   => \Omega\Database\PgsqlConnection::class,
            'sqlite'  => \Omega\Database\SqliteConnection::class,
            default   => throw new InvalidConfigurationException(
                "Unsupported database driver [$driver]."
            ),
        };
    }
}