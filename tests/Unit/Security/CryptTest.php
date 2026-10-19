<?php

declare(strict_types=1);

namespace Tests\Security;

use Omega\Security\Algo;
use Omega\Security\Crypt;
use Omega\Security\Exceptions\InvalidCipherDefinitionException;
use PHPUnit\Framework\Attributes\CoversClass;
use Tests\TestCase;

#[CoversClass(Algo::class)]
#[CoversClass(Crypt::class)]
final class CryptTest extends TestCase
{
    private Crypt $crypt;

    protected function setUp(): void
    {
        parent::setUp();

        $this->crypt = new Crypt('3sc3RLrpd17', Algo::AES_256_CBC);
    }

    public function testCanEncryptDecryptCorrectly(): void
    {
        $plainText = 'My secret message 1234';
        $encrypted = $this->crypt->encrypt($plainText);

        $this->assertSame($plainText, $this->crypt->decrypt($encrypted));
    }

    public function testCanEncryptCorrectlyWithCustomPassphrase(): void
    {
        $plainText = 'My secret message 1234';
        $encrypted = $this->crypt->encrypt($plainText, 'secret');

        $this->assertSame($plainText, $this->crypt->decrypt($encrypted, 'secret'));
    }

    public function testThrowsWhenTheCipherAlgorithmLacksACharactersLength(): void
    {
        $this->expectException(InvalidCipherDefinitionException::class);

        new Crypt('pass', 'aes-256-cbc');
    }

    public function testThrowsWhenTheCipherCharactersLengthIsNotPositive(): void
    {
        $this->expectException(InvalidCipherDefinitionException::class);

        new Crypt('pass', 'aes-256-cbc;0');
    }

    public function testThrowsWhenOpensslEncryptionFails(): void
    {
        $crypt = new Crypt('pass', 'unknown-cipher;16');

        $this->expectException(InvalidCipherDefinitionException::class);

        set_error_handler(static fn (): bool => true);

        try {
            $crypt->encrypt('data');
        } finally {
            restore_error_handler();
        }
    }

    public function testThrowsWhenOpensslEncryptionFailsWithACustomPassphrase(): void
    {
        $crypt = new Crypt('pass', 'unknown-cipher;16');

        $this->expectException(InvalidCipherDefinitionException::class);

        set_error_handler(static fn (): bool => true);

        try {
            $crypt->encrypt('data', 'secret');
        } finally {
            restore_error_handler();
        }
    }

    public function testThrowsWhenOpensslDecryptionFails(): void
    {
        $crypt = new Crypt('pass', 'unknown-cipher;16');

        $this->expectException(InvalidCipherDefinitionException::class);

        set_error_handler(static fn (): bool => true);

        try {
            $crypt->decrypt('Zm9v');
        } finally {
            restore_error_handler();
        }
    }

    public function testThrowsWhenOpensslDecryptionFailsWithACustomPassphrase(): void
    {
        $crypt = new Crypt('pass', 'unknown-cipher;16');

        $this->expectException(InvalidCipherDefinitionException::class);

        set_error_handler(static fn (): bool => true);

        try {
            $crypt->decrypt('Zm9v', 'secret');
        } finally {
            restore_error_handler();
        }
    }
}
