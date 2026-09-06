<?php

declare(strict_types=1);

namespace Tests\Database\RealDatabase;

use Omega\Database\Query\Query;
use PHPUnit\Framework\Attributes\CoversClass;
use Tests\Database\Asserts\UserTrait;
use Tests\Database\AbstractTestDatabase;

#[CoversClass(Query::class)]
final class InsertTest extends AbstractTestDatabase
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
    public function testItCanInsertData()
    {
        Query::from('users', $this->pdo)
            ->insert()
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
    public function testItCanInsertMultyRaw()
    {
        Query::from('users', $this->pdo)
            ->insert()
            ->rows([
                [
                    'user'      => 'sony',
                    'password'  => 'secret',
                    'stat'      => 1,
                ], [
                    'user'      => 'pradana',
                    'password'  => 'secret',
                    'stat'      => 2,
                ],
            ])
            ->execute();

        $this->assertUserExist('sony');
        $this->assertUserExist('pradana');
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
            ->insert()
            ->values([
                'user'      => 'sony',
                'password'  => 'secret',
                'stat'      => 66,
            ])
            ->on('stat')
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
            ->insert()
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
            ->on('user')
            ->on('stat')
            ->execute();

        $this->assertUserStat('sony', 66);
        $this->assertUserExist('sony2');
    }
}
