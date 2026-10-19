<?php

declare(strict_types=1);

namespace Tests\Validator\Collection;

use Omega\Collection\Collection;
use PHPUnit\Framework\Attributes\CoversClass;
use Tests\TestCase;

#[CoversClass(Collection::class)]
final class CheckingCollectionTest extends TestCase
{
    public function testCanCheckItemWithExistItem(): void
    {
        $this->assertTrue((new Collection(['key' => 'item']))->has('key'));
    }

    public function testCanCheckItemWithNotExistItem(): void
    {
        $this->assertFalse((new Collection([]))->has('key'));
    }
}
