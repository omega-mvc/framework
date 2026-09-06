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

use Omega\Database\Schema\Table\Alter;
use PHPUnit\Framework\Attributes\CoversClass;

/**
 * Class SchemaTest
 *
 * Provides database schema-related tests for the "users" table.
 * This includes altering tables using both blueprint-style methods
 * and raw SQL queries. Ensures that columns can be added, modified,
 * or dropped correctly without breaking the schema.
 *
 * @category  Tests
 * @package   Database
 * @link      https://omega-mvc.github.io
 * @author    Adriano Giovannini <agisoftt@gmail.com>
 * @copyright Copyright (c) 2025 - 2026 Adriano Giovannini (https://omega-mvc.github.io)
 * @license   https://www.gnu.org/licenses/gpl-3.0-standalone.html     GPL V3.0+
 * @version   2.0.0
 */
#[CoversClass(Alter::class)]
final class SchemaTest extends AbstractTestDatabase
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
     * Test it cna update database table.
     *
     * @return void
     */
    public function testItCanUpdateDatabaseTable(): void
    {
        $alter = $this->schema->alter('users', function (Alter $blueprint) {
            $blueprint->column('user')->varchar(20);
            $blueprint->drop('stat');
            $blueprint->add('status')->int(3);
        });

        $this->assertTrue($alter->execute());
    }

    /**
     * Test it can execute using raw query.
     *
     * @return void
     */
    public function testItCanExecuteUsingRawQuery(): void
    {
        /** @noinspection SqlResolve */
        $raw = $this->schema->raw(
            'ALTER TABLE testing_db.users
             MODIFY COLUMN user varchar(20),
             ADD COLUMN status int(3),
             DROP COLUMN stat'
        );

        $this->assertTrue($raw->execute());
    }
}
