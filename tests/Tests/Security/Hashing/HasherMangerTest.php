<?php

/**
 * Part of Omega - Tests\Security Package.
 *
 * @link      https://omega-mvc.github.io
 * @author    Adriano Giovannini <agisoftt@gmail.com>
 * @copyright Copyright (c) 2025 - 2026 Adriano Giovannini (https://omega-mvc.github.io)
 * @license   https://www.gnu.org/licenses/gpl-3.0-standalone.html     GPL V3.0+
 * @version   2.0.0
 */

declare(strict_types=1);

namespace Tests\Security\Hashing;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Omega\Security\Hashing\BcryptHasher;
use Omega\Security\Hashing\HashManager;

/**
 * Unit tests for the HashManager class.
 *
 * This test suite validates the default hashing behavior and ensures that
 * custom hashing drivers, such as BcryptHasher, can be set and used correctly.
 * It checks that hashed values differ from plaintext, and verifies that
 * password verification and algorithm validation work as expected.
 *
 * @category   Tests
 * @package    Security
 * @subpackage Hashing
 * @link       https://omega-mvc.github.io
 * @author     Adriano Giovannini <agisoftt@gmail.com>
 * @copyright  Copyright (c) 2025 - 2026 Adriano Giovannini (https://omega-mvc.github.io)
 * @license    https://www.gnu.org/licenses/gpl-3.0-standalone.html     GPL V3.0+
 * @version    2.0.0
 */
#[CoversClass(BcryptHasher::class)]
#[CoversClass(HashManager::class)]
class HasherMangerTest extends TestCase
{
    /**
     * Test it can hash default hasher.
     *
     * @return void
     */
    public function testItCanHashDefaultHasher(): void
    {
        $hasher = new HashManager();
        $hash   = $hasher->make('password');
        $this->assertNotSame('password', $hash);
        $this->assertTrue($hasher->verify('password', $hash));
        $this->assertTrue($hasher->isValidAlgorithm($hash));
    }

    /**
     * Test it can use driver.
     *
     * @return void
     */
    public function testItCanUsingDriver(): void
    {
        $hasher = new HashManager();
        $hasher->setDriver('bcrypt', new BcryptHasher());
        $hash   = $hasher->driver('bcrypt')->make('password');
        $this->assertNotSame('password', $hash);
        $this->assertTrue($hasher->driver('bcrypt')->verify('password', $hash));
        $this->assertTrue($hasher->driver('bcrypt')->isValidAlgorithm($hash));
    }
}
