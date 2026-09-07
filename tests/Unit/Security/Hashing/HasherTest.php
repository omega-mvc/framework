<?php

declare(strict_types=1);

namespace Tests\Security\Hashing;

use Omega\Security\Hashing\Argon2IdHasher;
use Omega\Security\Hashing\ArgonHasher;
use Omega\Security\Hashing\BcryptHasher;
use Omega\Security\Hashing\DefaultHasher;

covers(Argon2IdHasher::class);
covers(ArgonHasher::class);
covers(BcryptHasher::class);
covers(DefaultHasher::class);

it('can hash default hasher', function (): void {
    $hasher = new DefaultHasher();
    $hash   = $hasher->make('password');

    expect($hash)->not->toBe('password');
    expect($hasher->verify('password', $hash))->toBeTrue();
    expect($hasher->isValidAlgorithm($hash))->toBeTrue();
});

it('can hash bcrypt hasher', function (): void {
    $hasher = new BcryptHasher();
    $hash   = $hasher->make('password');

    expect($hash)->not->toBe('password');
    expect($hasher->verify('password', $hash))->toBeTrue();
    expect($hasher->isValidAlgorithm($hash))->toBeTrue();
});

it('can hash argon hasher', function (): void {
    $hasher = new ArgonHasher();
    $hash   = $hasher->make('password');

    expect($hash)->not->toBe('password');
    expect($hasher->verify('password', $hash))->toBeTrue();
    expect($hasher->isValidAlgorithm($hash))->toBeTrue();
});

it('can hash argon 2 id hasher', function (): void {
    $hasher = new Argon2IdHasher();
    $hash   = $hasher->make('password');

    expect($hash)->not->toBe('password');
    expect($hasher->verify('password', $hash))->toBeTrue();
    expect($hasher->isValidAlgorithm($hash))->toBeTrue();
});
