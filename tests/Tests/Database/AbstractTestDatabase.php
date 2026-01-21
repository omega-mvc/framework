<?php

declare(strict_types=1);

namespace Tests\Database;

use Omega\Database\Connection;
use Omega\Database\Schema\SchemaConnection;
use PHPUnit\Framework\TestCase;
use Omega\Database\DatabaseManager;
use Omega\Database\Query\Insert;
use Omega\Database\Schema\Schema;

abstract class AbstractTestDatabase extends TestCase
{
    /** @var array<string, string|int> */
    protected array $env;
    protected Connection $pdo;
    protected SchemaConnection $pdoSchema;
    protected Schema $schema;
    protected DatabaseManager $db;

    protected function createConnection(): void
    {
        $this->setupEnv($_ENV['DB_CONNECTION'] ?? 'mysql');
        $this->pdoSchema = new SchemaConnection($this->env);
        $this->schema    = new Schema($this->pdoSchema, $this->env['database']);

        // building the database
        $this->schema->create()->database($this->env['database'])->ifNotExists()->execute();

        $this->pdo = new Connection($this->env);
        $this->db  = new DatabaseManager($this->getConfiguration());
        $this->db->setDefaultConnection($this->pdo);
    }

    protected function dropConnection(): void
    {
        $this->schema->drop()->database($this->env['database'])->ifExists()->execute();
    }

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
     * @return array<string, array<string, string|int>>
     */
    protected function getConfiguration(): array
    {
        return [
            'mysql' => [
                'driver'   => 'mysql',
                'host'     => '127.0.0.1',
                'username' => 'root',
                'password' => 'vb65ty4',
                'database' => 'testing_db',
                'port'     => 3306,
                'charset'  => 'utf8mb4',
            ],
            'sqlite' => [
                'driver'   => 'sqlite',
                'database' => ':memory:',
                'prefix'   => '',
            ],
        ];
    }

    /**
     * @param string $useConnection
     * @return void
     */
    protected function setupEnv(string $useConnection = 'mysql'): void
    {
        $configuration = $this->getConfiguration();

        $this->env     = match ($useConnection) {
            'mysql', 'mariadb' => $configuration['mysql'],
            'sqlite' => $configuration['sqlite'],
        };
    }

    /**
     * Insert new Row of user.
     *
     * @param array<int, array<string, string|int|bool|null>> $users Format [{user, password, stat}]
     * @return bool
     */
    protected function createUser(array $users): bool
    {
        return new Insert('users', $this->pdo)
            ->rows($users)
            ->execute();
    }
}
