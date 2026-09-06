<?php

declare(strict_types=1);

namespace Tests\Database\RealDatabase;

use Omega\Database\Query\Query;
use Omega\Database\Query\Join\InnerJoin;
use PHPUnit\Framework\Attributes\CoversClass;
use Tests\Database\AbstractTestDatabase;

#[CoversClass(InnerJoin::class)]
#[CoversClass(Query::class)]
final class JoinTest extends AbstractTestDatabase
{
    protected function setUp(): void
    {
        $this->createConnection();
    }

    protected function tearDown(): void
    {
        $this->dropConnection();
    }

    // schema

    private function createUsersSchema(): bool
    {
        return $this
            ->pdo
            ->query('CREATE TABLE users (
                id INT AUTO_INCREMENT PRIMARY KEY,
                name VARCHAR(100) NOT NULL,
                email VARCHAR(100) UNIQUE NOT NULL,
                role_id INT NOT NULL
            )')
            ->execute();
    }

    private function createRolesSchema(): bool
    {
        return $this
            ->pdo
            ->query('CREATE TABLE roles (
                id INT AUTO_INCREMENT PRIMARY KEY,
                role_name VARCHAR(100) NOT NULL UNIQUE
            )')
            ->execute();
    }

    private function createLogsSchema(): bool
    {
        return $this
            ->pdo
            ->query('CREATE TABLE logs (
                id INT AUTO_INCREMENT PRIMARY KEY,
                user_id INT NOT NULL,
                action VARCHAR(255) NOT NULL,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (user_id) REFERENCES users(id)
            )')
            ->execute();
    }

    // factory

    private function factory()
    {
        $this->pdo
            ->query('INSERT INTO roles (role_name) VALUES
                ("Admin"),
                ("Editor"),
                ("Subscriber")')
            ->execute();

        $this->pdo
            ->query('INSERT INTO users (name, email, role_id) VALUES
                (\'Alice\', \'alice@example.com\', 1),
                (\'Bob\', \'bob@example.com\', 2),
                (\'Charlie\', \'charlie@example.com\', 3);')
        ->execute();

        $this->pdo
            ->query('INSERT INTO logs (user_id, action) VALUES
                (1, \'Logged In\'),
                (2, \'Logged In\'),
                (1, \'Deactivated\'),
                (3, \'Logged Out\');')
            ->execute();
    }

    // test

    /**
     * @test
     *
     * @group database
     */
    public function testItCanJoinInSelectQuery()
    {
        $this->createUsersSchema();
        $this->createRolesSchema();
        $this->createLogsSchema();
        $this->factory();

        $users = Query::from('users', $this->pdo)
            ->select(['users.name', 'roles.role_name'])
            ->join(InnerJoin::ref('roles', 'role_id', 'id'))
            ->get();

        $this->assertEquals('Alice', $users[0]['name']);
        $this->assertEquals('Admin', $users[0]['role_name']);
    }

    /**
     * @test
     *
     * @group database
     */
    public function testItCanJoinInUpdateQuery()
    {
        $this->createUsersSchema();
        $this->createRolesSchema();
        $this->createLogsSchema();
        $this->factory();

        Query::from('users', $this->pdo)
            ->update()
            ->value('name', 'Eve')
            ->join(InnerJoin::ref('roles', 'role_id', 'id'))
            ->equal('roles.role_name', 'Admin')
            ->execute();

        $users = $this->pdo->query('
            SELECT
                users.name, roles.role_name
            FROM users
            INNER JOIN roles ON
                users.role_id = roles.id
            ')
            ->resultset();

        $this->assertEquals('Eve', $users[0]['name']);
        $this->assertEquals('Admin', $users[0]['role_name']);
    }

    /**
     * @test
     *
     * @group database
     */
    public function testItCanJoinInDeleteQuery(): void
    {
        $this->createUsersSchema();
        $this->createRolesSchema();
        $this->createLogsSchema();
        $this->factory();

        // Delete related logs first
        Query::from('logs', $this->pdo)
            ->delete()
            ->alias('l')
            ->join(InnerJoin::ref('users', 'user_id', 'id'))
            ->equal('users.role_id', 1) // Assuming role_id 1 is for 'Admin'
            ->execute();

        Query::from('users', $this->pdo)
            ->delete()
            ->alias('u')
            ->join(InnerJoin::ref('roles', 'role_id', 'id'))
            ->equal('roles.role_name', 'Admin')
            ->execute();

        $users = $this->pdo->query('
            SELECT
                users.name, roles.role_name
            FROM users
            INNER JOIN roles ON
                users.role_id = roles.id
            ')
            ->resultset();

        $this->assertEquals('Bob', $users[0]['name']);
        $this->assertEquals('Editor', $users[0]['role_name']);
    }
}
