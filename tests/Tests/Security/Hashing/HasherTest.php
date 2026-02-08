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

use Omega\Security\Hashing\Argon2IdHasher;
use Omega\Security\Hashing\ArgonHasher;
use Omega\Security\Hashing\BcryptHasher;
use Omega\Security\Hashing\DefaultHasher;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for all available hashing implementations.
 *
 * This test suite validates the functionality of DefaultHasher, BcryptHasher,
 * ArgonHasher, and Argon2IdHasher. Each test ensures that plaintext values
 * are properly hashed, that verification against the original value succeeds,
 * and that the algorithm used is correctly recognized as valid.
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
#[CoversClass(Argon2IdHasher::class)]
#[CoversClass(ArgonHasher::class)]
#[CoversClass(BcryptHasher::class)]
#[CoversClass(DefaultHasher::class)]
final class HasherTest extends TestCase
{
    /**
     * Test it can hash default hasher.
     *
     * @return void
     */
    public function testItCanHashDefaultHasher(): void
    {
        $hasher = new DefaultHasher();
        $hash   = $hasher->make('password');
        $this->assertNotSame('password', $hash);
        $this->assertTrue($hasher->verify('password', $hash));
        $this->assertTrue($hasher->isValidAlgorithm($hash));
    }

    /**
     * Test it can hash bcrypt hasher.
     *
     * @return void
     */
    public function testItCanHashBcryptHasher(): void
    {
        $hasher = new BcryptHasher();
        $hash   = $hasher->make('password');
        $this->assertNotSame('password', $hash);
        $this->assertTrue($hasher->verify('password', $hash));
        $this->assertTrue($hasher->isValidAlgorithm($hash));
    }

    /**
     * Test it can hash argon hasher.
     *
     * @return void
     */
    public function testItCanHashArgonHasher(): void
    {
        $hasher = new ArgonHasher();
        $hash   = $hasher->make('password');
        $this->assertNotSame('password', $hash);
        $this->assertTrue($hasher->verify('password', $hash));
        $this->assertTrue($hasher->isValidAlgorithm($hash));
    }

    /**
     * Test it can hash argon 2 id hasher.
     *
     * @return void
     */
    public function testItCanHashArgon2IdHasher(): void
    {
        $hasher = new Argon2IdHasher();
        $hash   = $hasher->make('password');
        $this->assertNotSame('password', $hash);
        $this->assertTrue($hasher->verify('password', $hash));
        $this->assertTrue($hasher->isValidAlgorithm($hash));
    }
}
