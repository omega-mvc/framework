<?php

declare(strict_types=1);

namespace Tests\Database\RealDatabase;

use Omega\Database\Query\Query;
use PHPUnit\Framework\Attributes\CoversClass;
use Tests\Database\Asserts\UserTrait;
use Tests\Database\AbstractTestDatabase;

#[CoversClass(Query::class)]
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
    public function testItCanDelete(): void
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
    public function testItCanDeleteWithBetween(): void
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
    public function testItCanDeleteWithCompare(): void
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
    public function testItCanDeleteWithEqual(): void
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
    public function testItCanDeleteWithIn(): void
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
    public function testItCanDeleteWithLike(): void
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
    public function testItCanDeleteWithWhere(): void
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
    public function testItCanDeleteWithMultyCondition(): void
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
