<?php

declare(strict_types=1);

namespace Tests\Security;

use Omega\Security\Algo;
use Omega\Security\Crypt;

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
