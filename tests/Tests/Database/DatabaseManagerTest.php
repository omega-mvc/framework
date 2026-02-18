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

use Omega\Database\DatabaseManager;
use Omega\Database\Exceptions\InvalidConfigurationException;
use PHPUnit\Framework\Attributes\CoversClass;

/**
 * Class DatabaseManagerTest
 *
 * This test class covers the functionality of the DatabaseManager component.
 * It ensures that connections can be created, retrieved, and managed correctly
 * in a testing environment. The tests verify:
 *
 * - The ability to set a default database connection.
 * - Retrieving configured connections and executing basic queries.
 * - Proper exception handling when attempting to use a connection
 *   that has not been configured.
 *
 * Each test method sets up a fresh database schema and connection before
 * execution, and tears down any resources afterwards to avoid side effects
 * between tests.
 *
 * @category  Tests
 * @package   Database
 * @link      https://omega-mvc.github.io
 * @author    Adriano Giovannini <agisoftt@gmail.com>
 * @copyright Copyright (c) 2025 - 2026 Adriano Giovannini (https://omega-mvc.github.io)
 * @license   https://www.gnu.org/licenses/gpl-3.0-standalone.html     GPL V3.0+
 * @version   2.0.0
 */
#[CoversClass(DatabaseManager::class)]
#[CoversClass(InvalidConfigurationException::class)]
final class DatabaseManagerTest extends AbstractTestDatabase
{
    /**
     * Sets up the environment before each test method.
     *
     * This method is called automatically by PHPUnit before each test runs.
     * It is responsible for initializing the application instance, setting up
     * dependencies, and preparing any state required by the test.
     *
     * @return void
     */
    protected function setUp(): void
    {
        $this->createConnection();
        $this->createUserSchema();
    }

    /**
     * Tears down the environment after each test method.
     *
     * This method is called automatically by PHPUnit after each test runs.
     * It is responsible for cleaning up resources, flushing the application
     * state, unsetting properties, and resetting any static or global state
     * to avoid side effects between tests.
     *
     * @return void
     */
    protected function tearDown(): void
    {
        $this->dropConnection();
    }

    /**
     * Test it can set default connection.
     *
     * @return void
     */
    public function testItCanSetDefaultConnection(): void
    {
        $db = new DatabaseManager([
            'testing' => $this->env,
        ]);

        $db->setDefaultConnection($this->pdo);
        $this->assertTrue($db->query('SELECT * FROM users')->execute());
    }

    /**
     * Test it can get connection.
     *
     * @return void
     */
    public function testItCanGetConnection(): void
    {
        $db = new DatabaseManager([
            'testing' => $this->env,
        ]);

        $this->assertTrue($db->connection('testing')->query('SELECT * FROM users')->execute());
    }

    /**
     * Test it can throw exception when connection not configure.
     *
     * @return void
     */
    public function testItCanThrowExceptionWhenConnectionNotConfigure(): void
    {
        $db = new DatabaseManager([
            'invalid' => null,
        ]);

        $this->expectException(InvalidConfigurationException::class);
        $this->expectExceptionMessage('Database connection [invalid] not configured.');

        $this->assertTrue($db->connection('invalid')->query('SELECT * FROM users')->execute());
    }
}
