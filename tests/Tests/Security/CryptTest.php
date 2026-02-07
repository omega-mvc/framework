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

namespace Tests\Security;

use Omega\Security\Algo;
use Omega\Security\Crypt;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for the Crypt class.
 *
 * This test suite verifies the correct functionality of encryption and decryption
 * operations, including the ability to handle custom pass phrases. It ensures
 * that the Crypt class produces reversible and consistent results for secured data.
 *
 * @category  Tests
 * @package   Security
 * @link      https://omega-mvc.github.io
 * @author    Adriano Giovannini <agisoftt@gmail.com>
 * @copyright Copyright (c) 2025 - 2026 Adriano Giovannini (https://omega-mvc.github.io)
 * @license   https://www.gnu.org/licenses/gpl-3.0-standalone.html     GPL V3.0+
 * @version   2.0.0
 */
#[CoversClass(Algo::class)]
#[CoversClass(Crypt::class)]
class CryptTest extends TestCase
{
    /** @var Crypt Instance used for encrypting and decrypting test data. */
    private Crypt $crypt;

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
        parent::setUp();

        $this->crypt = new Crypt('3sc3RLrpd17', Algo::AES_256_CBC);
    }

    /**
     * Test it can encrypt decrypt correctly.
     *
     * @return void
     */
    public function testItCanEncryptDecryptCorrectly(): void
    {
        $planText  = 'My secret message 1234';
        $encrypted = $this->crypt->encrypt($planText);
        $decrypted = $this->crypt->decrypt($encrypted);

        $this->assertEquals($planText, $decrypted);
    }

    /**
     * Test it can encrypt correctly with custom pass phrase.
     *
     * @return void
     */
    public function testItCanEncryptCorrectlyWithCustomPassphrase(): void
    {
        $planText  = 'My secret message 1234';
        $encrypted = $this->crypt->encrypt($planText, 'secret');
        $decrypted = $this->crypt->decrypt($encrypted, 'secret');

        $this->assertEquals('My secret message 1234', $decrypted);
    }
}
