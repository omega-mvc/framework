<?php

declare(strict_types=1);

namespace Tests\Database\RealDatabase;

use Omega\Database\Query\Query;
use Tests\Database\Asserts\UserTrait;
use Tests\Database\AbstractTestDatabase;

final class DeleteTest extends AbstractTestDatabase
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
    public function testItCanDelete()
    {
        Query::from('users', $this->pdo)
            ->delete()
            ->execute()
        ;

        $this->assertUserNotExist('taylor');
    }

    /**
     * @test
     *
     * @group database
     */
    public function testItCanDeleteWithBetween()
    {
        Query::from('users', $this->pdo)
            ->delete()
            ->between('stat', 0, 100)
            ->execute()
        ;

        $this->assertUserNotExist('taylor');
    }

    /**
     * @test
     *
     * @group database
     */
    public function testItCanDeleteWithCompare()
    {
        Query::from('users', $this->pdo)
            ->delete()
            ->compare('user', '=', 'taylor')
            ->execute()
        ;

        $this->assertUserNotExist('taylor');
    }

    /**
     * @test
     *
     * @group database
     */
    public function testItCanDeleteWithEqual()
    {
        Query::from('users', $this->pdo)
            ->delete()
            ->equal('user', 'taylor')
            ->execute()
        ;

        $this->assertUserNotExist('taylor');
    }

    /**
     * @test
     *
     * @group database
     */
    public function testItCanDeleteWithIn()
    {
        Query::from('users', $this->pdo)
            ->delete()
            ->in('user', ['taylor'])
            ->execute()
        ;

        $this->assertUserNotExist('taylor');
    }

    /**
     * @test
     *
     * @group database
     */
    public function testItCanDeleteWithLike()
    {
        Query::from('users', $this->pdo)
            ->delete()
            ->like('user', 'tay%')
            ->execute()
        ;

        $this->assertUserNotExist('taylor');
    }

    /**
     * @test
     *
     * @group database
     */
    public function testItCanDeleteWithWhere()
    {
        Query::from('users', $this->pdo)
            ->delete()
            ->where('user = :user', [
                [':user', 'taylor'],
            ])
            ->execute()
        ;

        $this->assertUserNotExist('taylor');
    }

    /**
     * @test
     *
     * @group database
     */
    public function testItCanDeleteWithMultyCondition()
    {
        Query::from('users', $this->pdo)
            ->delete()
            ->compare('stat', '>', 1)
            ->where('user = :user', [
                [':user', 'taylor'],
            ])
            ->execute()
        ;

        $this->assertUserNotExist('taylor');
    }
}
