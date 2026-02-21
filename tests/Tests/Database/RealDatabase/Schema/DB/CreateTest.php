<?php

declare(strict_types=1);

namespace Tests\Database\RealDatabase\Schema\DB;

use Omega\Database\Schema\DB\Create;
use Tests\Database\AbstractTestDatabase;

final class CreateTest extends AbstractTestDatabase
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
        // need clean up
        $this->tearDown();
        $schema = new Create($this->env['database'], $this->pdo_schema);

        $this->assertTrue($schema->execute());
    }
}
