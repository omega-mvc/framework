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

it('rejects a non-bcrypt hash on the default hasher', function (): void {
    $hasher = new DefaultHasher();
    $argon  = password_hash('password', PASSWORD_ARGON2I);

    expect($hasher->isValidAlgorithm($argon))->toBeFalse();
});

it('can hash bcrypt hasher', function (): void {
    $hasher = new BcryptHasher();
    $hash   = $hasher->make('password');

    expect($hash)->not->toBe('password');
    expect($hasher->verify('password', $hash))->toBeTrue();
    expect($hasher->isValidAlgorithm($hash))->toBeTrue();
});

it('can configure bcrypt hasher rounds', function (): void {
    $hasher = new BcryptHasher();

    expect($hasher->setRounds(10))->toBe($hasher);

    $hash = $hasher->make('password');

    expect($hasher->info($hash)['options']['cost'])->toBe(10);
});

it('can hash bcrypt hasher with inline rounds option', function (): void {
    $hasher = new BcryptHasher();

    $hash = $hasher->make('password', ['rounds' => 9]);

    expect($hasher->info($hash)['options']['cost'])->toBe(9);
});

it('rejects a non-bcrypt hash on the bcrypt hasher', function (): void {
    $hasher = new BcryptHasher();
    $argon  = password_hash('password', PASSWORD_ARGON2I);

    expect($hasher->isValidAlgorithm($argon))->toBeFalse();
});

it('can configure argon hasher', function (): void {
    $hasher = new ArgonHasher();

    expect($hasher->setMemory(2048))->toBe($hasher);
    expect($hasher->setTime(4))->toBe($hasher);
    expect($hasher->setThreads(4))->toBe($hasher);

    $hash = $hasher->make('password');

    expect($hasher->verify('password', $hash))->toBeTrue();
});

it('can hash argon hasher with inline options', function (): void {
    $hasher = new ArgonHasher();

    $hash = $hasher->make('password', ['memory' => 1024, 'time' => 2, 'threads' => 2]);

    expect($hasher->verify('password', $hash))->toBeTrue();
});

it('rejects a non-argon2i hash on the argon hasher', function (): void {
    $hasher = new ArgonHasher();
    $bcrypt = password_hash('password', PASSWORD_BCRYPT);

    expect($hasher->isValidAlgorithm($bcrypt))->toBeFalse();
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

it('can hash argon 2 id hasher with inline options', function (): void {
    $hasher = new Argon2IdHasher();

    $hash = $hasher->make('password', ['memory' => 1024, 'time' => 2, 'threads' => 2]);

    expect($hasher->verify('password', $hash))->toBeTrue();
});

it('rejects a non-argon2id hash on the argon 2 id hasher', function (): void {
    $hasher = new Argon2IdHasher();
    $bcrypt = password_hash('password', PASSWORD_BCRYPT);

    expect($hasher->isValidAlgorithm($bcrypt))->toBeFalse();
});
