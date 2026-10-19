<?php

declare(strict_types=1);

namespace Tests\Validator\Collection;

use Omega\Collection\Collection;
use PHPUnit\Framework\Attributes\CoversClass;
use Tests\TestCase;

#[CoversClass(Collection::class)]
final class AddingCollectionTest extends TestCase
{
    public function testCanAddArrayUsingConstruct(): void
    {
        $collection = new Collection(['key' => 'value']);

        $this->assertEquals('value', $collection->key);
    }

    public function testCanAddArrayUsingMake(): void
    {
        $this->assertEquals('value', (new Collection(['key' => 'value']))->key);
    }

    public function testCanAddArrayUsingReplace(): void
    {
        /** @var Collection<string, string> $collection */
        $collection = new Collection([]);

        $this->assertEquals('value', $collection->replace(['key' => 'value'])->key);
    }

    public function testCanAddArrayUsingSet(): void
    {
        /** @var Collection<string, string> $collection */
        $collection = new Collection([]);

        $this->assertEquals('value', $collection->set('key', 'value')->key);
    }

    public function testCanEditExistArrayUsingSet(): void
    {
        $collection = new Collection(['key' => 'value']);

        $this->assertEquals('new value', $collection->set('key', 'new value')->key);
    }

    public function testCanAddArrayUsingMagicSet(): void
    {
        /** @var Collection<string, string> $collection */
        $collection = new Collection([]);

        $collection->key = 'value';

        $this->assertEquals('value', $collection->key);
    }

    public function testCanEditExistArrayUsingMagicSet(): void
    {
        $collection = new Collection(['key' => 'value']);

        $collection->key = 'new value';

        $this->assertEquals('new value', $collection->key);
    }
}
