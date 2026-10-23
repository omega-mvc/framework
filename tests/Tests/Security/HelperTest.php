<?php

declare(strict_types=1);

namespace Tests\Security;

use Omega\Security\Crypt;
use Omega\Security\Exceptions\InvalidCipherDefinitionException;
use PHPUnit\Framework\Attributes\CoversFunction;
use RuntimeException;
use Tests\TestCase;

use function Omega\Security\decrypt;
use function Omega\Security\encrypt;

final class HelperTest extends TestCase
{
    public function testEncryptHelperEncryptsDataWithAPassPhrase(): void
    {
        $encrypted = encrypt('secret-message', 'secret');

        $this->assertNotSame('secret-message', $encrypted);
    }

    public function testEncryptHelperRequiresAPassPhrase(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('A pass phrase is required to encrypt data.');

        encrypt('data');
    }

    public function testDecryptHelperRejectsPayloadsFromASeparateEncryptCall(): void
    {
        $encrypted = encrypt('secret-message', 'secret');

        $this->expectException(InvalidCipherDefinitionException::class);
        $this->expectExceptionMessage('Unable to decrypt payload using the configured cipher.');

        decrypt($encrypted, 'secret');
    }

    public function testDecryptHelperRequiresAPassPhrase(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('A pass phrase is required to decrypt data.');

        decrypt('data');
    }

    public function testDecryptHelperReturnsWhenThePaddingSurvivesTheMismatchedIv(): void
    {
        $plain     = str_repeat('message', 8);
        $decrypted = decrypt(encrypt($plain, 'secret'), 'secret');

        $this->assertNotSame('', $decrypted);
        $this->assertSame(strlen($plain), strlen($decrypted));
        $this->assertSame(substr($plain, 16), substr($decrypted, 16));
    }
}
