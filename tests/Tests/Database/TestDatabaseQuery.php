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

use Omega\Database\Connection;
use Omega\Database\Schema\SchemaConnection;
use PHPUnit\Framework\MockObject\Exception as PHPUnitMockException;
use PHPUnit\Framework\TestCase;

/**
 * Class TestDatabaseQuery
 *
 * Abstract base class for database query tests.
 * Provides setup and teardown for mocked database connections
 * using PHPUnit native stubs. Ensures isolation of test cases
 * and avoids side effects on real databases.
 *
 * @category  Tests
 * @package   Database
 * @link      https://omega-mvc.github.io
 * @author    Adriano Giovannini <agisoftt@gmail.com>
 * @copyright Copyright (c) 2025 - 2026 Adriano Giovannini (https://omega-mvc.github.io)
 * @license   https://www.gnu.org/licenses/gpl-3.0-standalone.html     GPL V3.0+
 * @version   2.0.0
 */
abstract class TestDatabaseQuery extends TestCase
{
    /** @var Connection Stub for the main database connection */
    protected Connection $pdo;

    /** @var SchemaConnection Stub for the schema-specific connection */
    protected SchemaConnection $pdoSchema;

    /**
     * Sets up the environment before each test method.
     *
     * Initializes stubs for Connection and SchemaConnection to isolate
     * tests from the actual database.
     *
     * @return void
     * @throws PHPUnitMockException Thrown when the stubbed method is configured to simulate an error scenario.
     */
    protected function setUp(): void
    {
        $this->pdo       = $this->createStub(Connection::class);
        $this->pdoSchema = $this->createStub(SchemaConnection::class);
    }

    /**
     * Tears down the environment after each test method.
     *
     * Cleans up any references and resets state to ensure no test
     * interference.
     *
     * @return void
     */
    protected function tearDown(): void
    {
        unset($this->pdo, $this->pdoSchema);
    }
}
