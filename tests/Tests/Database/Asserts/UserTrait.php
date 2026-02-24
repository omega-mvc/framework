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
namespace Tests\Database\Asserts;

use Omega\Database\Query\Query;
use PHPUnit\Framework\Attributes\CoversClass;

use function PHPUnit\Framework\assertEquals;
use function PHPUnit\Framework\assertTrue;

/**
 * Trait UserTrait
 *
 * Provides helper assertions for testing user data in the database.
 * Intended for use in test classes that have access to a PDO instance.
 *
 * Includes methods to assert the existence, non-existence, and specific
 * statistics of users in the "users" table.
 *
 * @category   Tests
 * @package    Database
 * @subpackage Assert
 * @link       https://omega-mvc.github.io
 * @author     Adriano Giovannini <agisoftt@gmail.com>
 * @copyright  Copyright (c) 2025 - 2026 Adriano Giovannini (https://omega-mvc.github.io)
 * @license    https://www.gnu.org/licenses/gpl-3.0-standalone.html     GPL V3.0+
 * @version    2.0.0
 */
#[CoversClass(Query::class)]
trait UserTrait
{
    /**
     * Assert that a user exists in the database.
     *
     * Queries the "users" table for the specified username and
     * asserts that exactly one record exists.
     *
     * @param string $user The username to check for existence.
     * @return void
     */
    protected function assertUserExist(string $user): void
    {
        $data = Query::from('users', $this->pdo)
            ->select(['user'])
            ->equal('user', $user)
            ->all();

        assertTrue(count($data) === 1, 'expect user exist in database');
    }

    /**
     * Assert that a user does not exist in the database.
     *
     * Queries the "users" table for the specified username and
     * asserts that no records are found.
     *
     * @param string $user The username to check for non-existence.
     * @return void
     */
    protected function assertUserNotExist(string $user): void
    {
        $data = Query::from('users', $this->pdo)
            ->select(['user'])
            ->equal('user', $user)
            ->all();

        assertTrue(count($data) === 0, 'expect user not exist in database');
    }

    /**
     * Assert that a user's statistic matches the expected value.
     *
     * Queries the "users" table for the specified username and
     * asserts that the 'stat' field matches the expected integer value.
     *
     * @param string $user The username whose statistic will be checked.
     * @param int $expect The expected value of the user's 'stat' field.
     * @return void
     */
    protected function assertUserStat(string $user, int $expect): void
    {
        $data = Query::from('users', $this->pdo)
            ->select(['stat'])
            ->equal('user', $user)
            ->all();

        assertEquals($expect, (int) $data[0]['stat'], 'expect user stat');
    }
}
