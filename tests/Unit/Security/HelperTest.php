<?php

declare(strict_types=1);

namespace Tests\Security;

use Omega\Security\Crypt;
use Omega\Security\Exceptions\InvalidCipherDefinitionException;
use RuntimeException;

use function Omega\Security\decrypt;
use function Omega\Security\encrypt;

covers('Omega\Security\encrypt');
covers('Omega\Security\decrypt');

it('encrypt helper encrypts data with a pass phrase', function (): void {
    $encrypted = encrypt('secret-message', 'secret');

    expect($encrypted)->not->toBe('secret-message');
});

it('encrypt helper requires a pass phrase', function (): void {
    encrypt('data');
})->throws(RuntimeException::class, 'A pass phrase is required to encrypt data.');

it('decrypt helper rejects payloads from a separate encrypt call', function (): void {
    $encrypted = encrypt('secret-message', 'secret');

    decrypt($encrypted, 'secret');
})->throws(InvalidCipherDefinitionException::class, 'Unable to decrypt payload using the configured cipher.');

it('decrypt helper requires a pass phrase', function (): void {
    decrypt('data');
})->throws(RuntimeException::class, 'A pass phrase is required to decrypt data.');

it('decrypt helper returns when the padding survives the mismatched iv', function (): void {
    $plain     = str_repeat('message', 8);
    $decrypted = decrypt(encrypt($plain, 'secret'), 'secret');

    expect($decrypted)->not->toBe('')
        ->and(strlen($decrypted))->toBe(strlen($plain))
        ->and(substr($decrypted, 16))->toBe(substr($plain, 16));
});