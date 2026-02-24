<?php

declare(strict_types=1);

namespace Tests\Database\RealDatabase;

use Omega\Database\Query\Query;
use PHPUnit\Framework\Attributes\CoversClass;use Tests\Database\Asserts\UserTrait;
use Tests\Database\AbstractTestDatabase;

#[CoversClass(Query::class)]
final class ReplaceTest extends AbstractTestDatabase
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
    public function testItCanReplaceOnNewData()
    {
        Query::from('users', $this->pdo)
            ->replace()
            ->values([
                'user'      => 'sony',
                'password'  => 'secret',
                'stat'      => 99,
            ])
            ->execute();

        $this->assertUserExist('sony');
    }

    /**
     * @test
     *
     * @group database
     */
    public function testItCanReplaceOnExistData()
    {
        Query::from('users', $this->pdo)
            ->insert()
            ->values([
                'user'      => 'sony',
                'password'  => 'secret',
                'stat'      => 99,
            ])
            ->execute();

        Query::from('users', $this->pdo)
            ->replace()
            ->values([
                'user'      => 'sony',
                'password'  => 'secret',
                'stat'      => 66,
            ])
            ->execute();

        $this->assertUserStat('sony', 66);
    }

    /**
     * @test
     *
     * @group database
     */
    public function testItCanUpdateInsertusingOneQuery()
    {
        Query::from('users', $this->pdo)
            ->insert()
            ->values([
                'user'      => 'sony',
                'password'  => 'secret',
                'stat'      => 99,
            ])
            ->execute();

        Query::from('users', $this->pdo)
            ->replace()
            ->rows([
                [
                    'user'      => 'sony',
                    'password'  => 'secret',
                    'stat'      => 66,
                ],
                [
                    'user'      => 'sony2',
                    'password'  => 'secret',
                    'stat'      => 66,
                ],
            ])
            ->execute();

        $this->assertUserStat('sony', 66);
        $this->assertUserExist('sony2');
    }
}
