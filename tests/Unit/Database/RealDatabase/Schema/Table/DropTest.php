<?php

declare(strict_types=1);

namespace Tests\Database\RealDatabase\Schema\Table;

use Omega\Database\Schema\Table\Drop;
use PHPUnit\Framework\Attributes\CoversClass;
use Tests\Database\Asserts\UserTrait;
use Tests\Database\AbstractTestDatabase;

#[CoversClass((Drop::class))]
final class DropTest extends AbstractTestDatabase
{
    use UserTrait;

    protected function setUp(): void
    {
        $this->createConnection();
        $this->createUserSchema();
        $this->createUser([
            [
                'user'     => 'taylor',
                'password' => 'secret',
                'stat'     => 99,
            ],
        ]);
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
    public function testItCanGenerateDropDatabase()
    {
        $schema = new Drop($this->env['database'], 'users', $this->pdoSchema);

        $this->assertTrue($schema->execute());
    }
}
