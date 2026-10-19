<?php

declare(strict_types=1);

namespace Tests\Security\Hashing;

use Omega\Security\Hashing\BcryptHasher;
use Omega\Security\Hashing\DefaultHasher;
use Omega\Security\Hashing\HashManager;
use PHPUnit\Framework\Attributes\CoversClass;
use Tests\TestCase;

#[CoversClass(BcryptHasher::class)]
#[CoversClass(HashManager::class)]
final class HasherMangerTest extends TestCase
{
    public function testCanHashDefaultHasher(): void
    {
        $hasher = new HashManager();
        $hash   = $hasher->make('password');

        $this->assertNotSame('password', $hash);
        $this->assertTrue($hasher->verify('password', $hash));
        $this->assertTrue($hasher->isValidAlgorithm($hash));
    }

    public function testCanUseDriver(): void
    {
        $hasher = new HashManager();
        $hasher->setDriver('bcrypt', new BcryptHasher());

        $hash = $hasher->driver('bcrypt')->make('password');

        $this->assertNotSame('password', $hash);
        $this->assertTrue($hasher->driver('bcrypt')->verify('password', $hash));
        $this->assertTrue($hasher->driver('bcrypt')->isValidAlgorithm($hash));
    }

    public function testCanDelegateInfoToTheDefaultDriver(): void
    {
        $hasher = new HashManager();
        $hash   = $hasher->make('password');

        $this->assertSame('bcrypt', $hasher->info($hash)['algoName']);
    }

    public function testReturnsTheDefaultDriverForAnUnknownDriverName(): void
    {
        $hasher = new HashManager();
        $hasher->setDriver('bcrypt', new BcryptHasher());

        $this->assertInstanceOf(DefaultHasher::class, $hasher->driver('unknown'));
    }

    public function testCanSetTheDefaultDriver(): void
    {
        $hasher = new HashManager();
        $bcrypt = new BcryptHasher();

        $this->assertSame($hasher, $hasher->setDefaultDriver($bcrypt));

        $hash = $hasher->make('password');

        $this->assertSame($bcrypt, $hasher->driver());
        $this->assertSame('bcrypt', $hasher->info($hash)['algoName']);
    }
}
