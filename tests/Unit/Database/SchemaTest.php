<?php

declare(strict_types=1);

namespace Tests\Database;

use Omega\Database\Schema\Table\Alter;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Database\ManagesDatabase;
use Tests\TestCase;

#[CoversClass(Alter::class)]
final class SchemaTest extends TestCase
{
    use ManagesDatabase;

    protected function tearDown(): void
    {
        $this->dropConnection();

        parent::tearDown();
    }

    #[DataProvider('engineProvider')]
    public function testItCanUpdateDatabaseTable(string $engine): void
    {
        $this->requiresOneOf($engine, ['mysql', 'mariadb'], 'MySQL ALTER TABLE syntax');

        $this->createConnection($engine);
        $this->createUserSchema();

        $alter = $this->schema->alter('users', function (Alter $blueprint) {
            $blueprint->column('user')->varchar(20);
            $blueprint->drop('stat');
            $blueprint->add('status')->int(3);
        });

        $this->assertTrue($alter->execute());
    }

    #[DataProvider('engineProvider')]
    public function testItCanExecuteUsingRawQuery(string $engine): void
    {
        $this->requiresOneOf($engine, ['mysql', 'mariadb'], 'MySQL ALTER TABLE syntax');

        $this->createConnection($engine);
        $this->createUserSchema();

        $raw = $this->schema->raw(
            'ALTER TABLE ' . $this->pdoSchema->getDatabase() . '.users
             MODIFY COLUMN user varchar(20),
             ADD COLUMN status int(3),
             DROP COLUMN stat'
        );

        $this->assertTrue($raw->execute());
    }
}
