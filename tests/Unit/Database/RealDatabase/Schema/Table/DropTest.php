<?php

declare(strict_types=1);

namespace Tests\Database\RealDatabase\Schema\Table;

use Omega\Database\Schema\Table\Drop;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Database\Asserts\UserTrait;
use Tests\Database\ManagesDatabase;
use Tests\TestCase;

#[CoversClass(Drop::class)]
final class DropTest extends TestCase
{
    use ManagesDatabase;
    use UserTrait;

    protected function tearDown(): void
    {
        $this->dropConnection();

        parent::tearDown();
    }

    #[DataProvider('engineProvider')]
    public function testItCanGenerateDropDatabase(string $engine): void
    {
        $this->requiresOneOf($engine, ['mysql', 'mariadb'], 'database-qualified DROP TABLE');

        $this->createConnection($engine);
        $this->seedDefaultUser();

        $schema = new Drop($this->pdoSchema->getDatabase(), 'users', $this->pdoSchema);

        $this->assertTrue($schema->execute());
    }
}
