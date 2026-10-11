<?php

declare(strict_types=1);

namespace Tests\Database\RealDatabase\Schema\DB;

use Omega\Database\Schema\DB\Drop;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Database\ManagesDatabase;
use Tests\TestCase;

#[CoversClass(Drop::class)]
final class DropTest extends TestCase
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
        $this->requiresOneOf($engine, ['mysql', 'mariadb', 'pgsql'], 'DROP DATABASE statement');

        $this->createConnection($engine);

        $schema = new Drop($this->pdoSchema->getDatabase(), $this->pdoSchema);

        $this->assertTrue($schema->execute());
    }
}
