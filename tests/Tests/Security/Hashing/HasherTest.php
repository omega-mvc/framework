<?php

declare(strict_types=1);

namespace Tests\Security\Hashing;

use Omega\Security\Hashing\Argon2IdHasher;
use Omega\Security\Hashing\ArgonHasher;
use Omega\Security\Hashing\BcryptHasher;
use Omega\Security\Hashing\DefaultHasher;
use PHPUnit\Framework\Attributes\CoversClass;
use Tests\TestCase;

#[CoversClass(Argon2IdHasher::class)]
#[CoversClass(ArgonHasher::class)]
#[CoversClass(BcryptHasher::class)]
#[CoversClass(DefaultHasher::class)]
final class HasherTest extends TestCase
{
    public function testCanHashDefaultHasher(): void
    {
        $hasher = new DefaultHasher();
        $hash   = $hasher->make('password');

        $this->assertNotSame('password', $hash);
        $this->assertTrue($hasher->verify('password', $hash));
        $this->assertTrue($hasher->isValidAlgorithm($hash));
    }

    public function testRejectsANonBcryptHashOnTheDefaultHasher(): void
    {
        $hasher = new DefaultHasher();
        $argon  = password_hash('password', PASSWORD_ARGON2I);

        $this->assertFalse($hasher->isValidAlgorithm($argon));
    }

    public function testCanHashBcryptHasher(): void
    {
        $hasher = new BcryptHasher();
        $hash   = $hasher->make('password');

        $this->assertNotSame('password', $hash);
        $this->assertTrue($hasher->verify('password', $hash));
        $this->assertTrue($hasher->isValidAlgorithm($hash));
    }

    public function testCanConfigureBcryptHasherRounds(): void
    {
        $hasher = new BcryptHasher();

        $this->assertSame($hasher, $hasher->setRounds(10));

        $hash = $hasher->make('password');

        $this->assertSame(10, $hasher->info($hash)['options']['cost']);
    }

    public function testCanHashBcryptHasherWithInlineRoundsOption(): void
    {
        $hasher = new BcryptHasher();

        $hash = $hasher->make('password', ['rounds' => 9]);

        $this->assertSame(9, $hasher->info($hash)['options']['cost']);
    }

    public function testRejectsANonBcryptHashOnTheBcryptHasher(): void
    {
        $hasher = new BcryptHasher();
        $argon  = password_hash('password', PASSWORD_ARGON2I);

        $this->assertFalse($hasher->isValidAlgorithm($argon));
    }

    public function testCanConfigureArgonHasher(): void
    {
        $hasher = new ArgonHasher();

        $this->assertSame($hasher, $hasher->setMemory(2048));
        $this->assertSame($hasher, $hasher->setTime(4));
        $this->assertSame($hasher, $hasher->setThreads(4));

        $hash = $hasher->make('password');

        $this->assertTrue($hasher->verify('password', $hash));
    }

    public function testCanHashArgonHasherWithInlineOptions(): void
    {
        $hasher = new ArgonHasher();

        $hash = $hasher->make('password', ['memory' => 1024, 'time' => 2, 'threads' => 2]);

        $this->assertTrue($hasher->verify('password', $hash));
    }

    public function testRejectsANonArgon2iHashOnTheArgonHasher(): void
    {
        $hasher = new ArgonHasher();
        $bcrypt = password_hash('password', PASSWORD_BCRYPT);

        $this->assertFalse($hasher->isValidAlgorithm($bcrypt));
    }

    public function testCanHashArgonHasher(): void
    {
        $hasher = new ArgonHasher();
        $hash   = $hasher->make('password');

        $this->assertNotSame('password', $hash);
        $this->assertTrue($hasher->verify('password', $hash));
        $this->assertTrue($hasher->isValidAlgorithm($hash));
    }

    public function testCanHashArgon2IdHasher(): void
    {
        $hasher = new Argon2IdHasher();
        $hash   = $hasher->make('password');

        $this->assertNotSame('password', $hash);
        $this->assertTrue($hasher->verify('password', $hash));
        $this->assertTrue($hasher->isValidAlgorithm($hash));
    }

    public function testCanHashArgon2IdHasherWithInlineOptions(): void
    {
        $hasher = new Argon2IdHasher();

        $hash = $hasher->make('password', ['memory' => 1024, 'time' => 2, 'threads' => 2]);

        $this->assertTrue($hasher->verify('password', $hash));
    }

    public function testRejectsANonArgon2idHashOnTheArgon2IdHasher(): void
    {
        $hasher = new Argon2IdHasher();
        $bcrypt = password_hash('password', PASSWORD_BCRYPT);

        $this->assertFalse($hasher->isValidAlgorithm($bcrypt));
    }
}
