<?php

declare(strict_types=1);

namespace Tests\Security\Hashing;

use Omega\Security\Hashing\BcryptHasher;
use Omega\Security\Hashing\HashManager;

covers(BcryptHasher::class);
covers(HashManager::class);

it('can hash default hasher', function (): void {
    $hasher = new HashManager();
    $hash   = $hasher->make('password');

    expect($hash)->not->toBe('password');
    expect($hasher->verify('password', $hash))->toBeTrue();
    expect($hasher->isValidAlgorithm($hash))->toBeTrue();
});

it('can use driver', function (): void {
    $hasher = new HashManager();
    $hasher->setDriver('bcrypt', new BcryptHasher());

    $hash = $hasher->driver('bcrypt')->make('password');

    expect($hash)->not->toBe('password');
    expect($hasher->driver('bcrypt')->verify('password', $hash))->toBeTrue();
    expect($hasher->driver('bcrypt')->isValidAlgorithm($hash))->toBeTrue();
});
