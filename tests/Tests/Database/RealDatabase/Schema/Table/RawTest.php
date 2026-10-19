<?php

declare(strict_types=1);

namespace Tests\Database\RealDatabase\Schema\Table;

use Omega\Database\Schema\Table\Raw;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Database\ManagesDatabase;
use Tests\TestCase;

#[CoversClass(Raw::class)]
final class RawTest extends TestCase
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
        $this->requiresOneOf($engine, ['mysql', 'mariadb'], 'database-qualified raw SQL');

        $this->createConnection($engine);

        $schema = new Raw(
            'CREATE TABLE ' . $this->pdoSchema->getDatabase() . '.test ( PersonID int, LastName varchar(255), PRIMARY KEY (PersonID) )',
            $this->pdoSchema
        );

        $this->assertTrue($schema->execute());
    }
}
