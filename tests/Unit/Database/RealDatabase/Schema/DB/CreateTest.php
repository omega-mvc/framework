<?php

declare(strict_types=1);

namespace Tests\Database\RealDatabase\Schema\DB;

use Omega\Database\Schema\DB\Create;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Database\ManagesDatabase;
use Tests\TestCase;

#[CoversClass(Create::class)]
final class CreateTest extends TestCase
{
    use ManagesDatabase;

    protected function tearDown(): void
    {
        $this->dropConnection();

        parent::tearDown();
    }

    #[DataProvider('engineProvider')]
    public function testItCanGenerateCreateDatabase(string $engine): void
    {
        $this->requiresOneOf($engine, ['mysql', 'mariadb', 'pgsql'], 'CREATE DATABASE statement');

        $this->createConnection($engine);

        // clean up so the database can be created from scratch
        $this->dropConnection();

        $schema = new Create($this->pdoSchema->getDatabase(), $this->pdoSchema);

        $this->assertTrue($schema->execute());
    }
}
