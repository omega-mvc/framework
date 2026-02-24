<?php

declare(strict_types=1);

namespace Tests\Database\RealDatabase\Schema\DB;

use Omega\Database\Schema\DB\Drop;
use PHPUnit\Framework\Attributes\CoversClass;
use Tests\Database\AbstractTestDatabase;

#[CoversClass(Drop::class)]
final class DropTest extends AbstractTestDatabase
{
    protected function setUp(): void
    {
        $this->createConnection();
    }

    protected function tearDown(): void
    {
        $this->dropConnection();
    }

    /**
     * @test
     *
     * @group database
     */
    public function testItCanGenerateCreateDatabase()
    {
        $schema = new Drop($this->env['database'], $this->pdoSchema);

        $this->assertTrue($schema->execute());
    }
}
