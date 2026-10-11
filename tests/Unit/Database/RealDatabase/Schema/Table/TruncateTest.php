<?php

declare(strict_types=1);

namespace Tests\Database\RealDatabase\Schema\Table;

use Omega\Database\Schema\Table\Truncate;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Database\Asserts\UserTrait;
use Tests\Database\ManagesDatabase;
use Tests\TestCase;

#[CoversClass(Truncate::class)]
final class TruncateTest extends TestCase
{
    use ManagesDatabase;
    use UserTrait;

    protected function tearDown(): void
    {
        $this->dropConnection();

        parent::tearDown();
    }

    #[DataProvider('engineProvider')]
    public function testItCanGenerateTruncateDatabase(string $engine): void
    {
        $this->requiresOneOf($engine, ['mysql', 'mariadb'], 'TRUNCATE TABLE statement');

        $this->createConnection($engine);
        $this->seedDefaultUser();

        $schema = new Truncate($this->pdoSchema->getDatabase(), 'users', $this->pdoSchema);

        $this->assertTrue($schema->execute());
    }
}
