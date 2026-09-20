<?php

declare(strict_types=1);

namespace Tests\Security;

use Omega\Security\Algo;
use Omega\Security\Crypt;
use Omega\Security\Exceptions\InvalidCipherDefinitionException;

covers(Algo::class);
covers(Crypt::class);

beforeEach(function (): void {
    $this->crypt = new Crypt('3sc3RLrpd17', Algo::AES_256_CBC);
});

it('can encrypt decrypt correctly', function (): void {
    $plainText = 'My secret message 1234';
    $encrypted = $this->crypt->encrypt($plainText);

    expect($this->crypt->decrypt($encrypted))->toBe($plainText);
});

it('can encrypt correctly with custom passphrase', function (): void {
    $plainText = 'My secret message 1234';
    $encrypted = $this->crypt->encrypt($plainText, 'secret');

    expect($this->crypt->decrypt($encrypted, 'secret'))->toBe($plainText);
});

it('throws when the cipher algorithm lacks a characters length', function (): void {
    new Crypt('pass', 'aes-256-cbc');
})->throws(InvalidCipherDefinitionException::class);

it('throws when the cipher characters length is not positive', function (): void {
    new Crypt('pass', 'aes-256-cbc;0');
})->throws(InvalidCipherDefinitionException::class);

it('throws when openssl encryption fails', function (): void {
    $crypt = new Crypt('pass', 'unknown-cipher;16');

    set_error_handler(static fn (): bool => true);

    try {
        $crypt->encrypt('data');
    } finally {
        restore_error_handler();
    }
})->throws(InvalidCipherDefinitionException::class);

it('throws when openssl encryption fails with a custom passphrase', function (): void {
    $crypt = new Crypt('pass', 'unknown-cipher;16');

    set_error_handler(static fn (): bool => true);

    try {
        $crypt->encrypt('data', 'secret');
    } finally {
        restore_error_handler();
    }
})->throws(InvalidCipherDefinitionException::class);

it('throws when openssl decryption fails', function (): void {
    $crypt = new Crypt('pass', 'unknown-cipher;16');

    set_error_handler(static fn (): bool => true);

    try {
        $crypt->decrypt('Zm9v');
    } finally {
        restore_error_handler();
    }
})->throws(InvalidCipherDefinitionException::class);

it('throws when openssl decryption fails with a custom passphrase', function (): void {
    $crypt = new Crypt('pass', 'unknown-cipher;16');

    set_error_handler(static fn (): bool => true);

    try {
        $crypt->decrypt('Zm9v', 'secret');
    } finally {
        restore_error_handler();
    }
})->throws(InvalidCipherDefinitionException::class);
