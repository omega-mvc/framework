<?php

declare(strict_types=1);

namespace Tests\Database\RealDatabase;

use Omega\Database\Query\Query;
use Omega\Database\Query\Join\InnerJoin;
use PHPUnit\Framework\Attributes\CoversClass;
use Tests\Database\Asserts\UserTrait;
use Tests\Database\AbstractTestDatabase;

#[CoversClass(InnerJoin::class)]
#[CoversClass(Query::class)]
final class SelectTest extends AbstractTestDatabase
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

    private function profileFactory()
    {
        // factory
        $this->pdo
            ->query('CREATE TABLE profiles (
                user varchar(32) NOT NULL,
                real_name varchar(500) NOT NULL,
                PRIMARY KEY (user)
              )')
            ->execute();

        $this->pdo
            ->query('INSERT INTO profiles (
                user,
                real_name
              ) VALUES (
                :user,
                :real_name
              )')
            ->bind(':user', 'taylor')
            ->bind(':real_name', 'taylor otwell')
            ->execute();
    }

    /**
     * @test
     *
     * @group database
     */
    public function testItCanSelectQuery()
    {
        $users = Query::from('users', $this->pdo)
            ->select()
            ->all()
        ;

        $this->assertArrayHasKey('user', $users[0]);
        $this->assertArrayHasKey('password', $users[0]);
        $this->assertArrayHasKey('stat', $users[0]);
    }

    /**
     * @test
     *
     * @group database
     */
    public function testItCanSelectQueryOnlyuser()
    {
        $users = Query::from('users', $this->pdo)
            ->select(['user'])
            ->all()
        ;

        $this->assertArrayHasKey('user', $users[0]);
        $this->assertArrayNotHasKey('password', $users[0]);
        $this->assertArrayNotHasKey('stat', $users[0]);
    }

    /**
     * @test
     *
     * @group database
     */
    public function testItCanSelectQueryWithBetween()
    {
        $users = Query::from('users', $this->pdo)
            ->select()
            ->between('stat', 0, 100)
            ->all()
        ;

        $this->assertEquals('taylor', $users[0]['user']);
    }

    /**
     * @test
     *
     * @group database
     */
    public function testItCanSelectQueryWithCompare()
    {
        $users = Query::from('users', $this->pdo)
            ->select()
            ->compare('user', '=', 'taylor')
            ->all()
        ;

        $this->assertEquals('taylor', $users[0]['user']);
    }

    /**
     * @test
     *
     * @group database
     */
    public function testItCanSelectQueryWithEqual()
    {
        $users = Query::from('users', $this->pdo)
            ->select()
            ->equal('user', 'taylor')
            ->all()
        ;

        $this->assertEquals('taylor', $users[0]['user']);
    }

    /**
     * @test
     *
     * @group database
     */
    public function testItCanSelectQueryWithIn()
    {
        $users = Query::from('users', $this->pdo)
            ->select()
            ->in('user', ['taylor'])
            ->all()
        ;

        $this->assertEquals('taylor', $users[0]['user']);
    }

    /**
     * @test
     *
     * @group database
     */
    public function testItCanSelectQueryWithLike()
    {
        $users = Query::from('users', $this->pdo)
            ->select()
            ->like('user', 'tay%')
            ->all()
        ;

        $this->assertEquals('taylor', $users[0]['user']);
    }

    /**
     * @test
     *
     * @group database
     */
    public function testItCanSelectQueryWithWhere()
    {
        $users = Query::from('users', $this->pdo)
            ->select()
            ->where('user = :user', [
                [':user', 'taylor'],
            ])
            ->all()
        ;

        $this->assertEquals('taylor', $users[0]['user']);
    }

    /**
     * @test
     *
     * @group database
     */
    public function testItCanSelectQueryWithMultyCondition()
    {
        $users = Query::from('users', $this->pdo)
            ->select()
            ->compare('stat', '>', 1)
            ->where('user = :user', [
                [':user', 'taylor'],
            ])
            ->all()
        ;

        $this->assertEquals('taylor', $users[0]['user']);
    }

    /**
     * @test
     *
     * @group database
     */
    public function testItCanSelectQueryWithLimit()
    {
        $users = Query::from('users', $this->pdo)
            ->select()
            ->limit(0, 1)
            ->all()
        ;

        $this->assertArrayHasKey('user', $users[0]);
        $this->assertArrayHasKey('password', $users[0]);
        $this->assertArrayHasKey('stat', $users[0]);
    }

    /**
     * @test
     *
     * @group database
     */
    public function testItCanSelectQueryWithOffset()
    {
        $users = Query::from('users', $this->pdo)
            ->select()
            ->limitStart(0)
            ->offset(1)
            ->all()
        ;

        $this->assertArrayHasKey('user', $users[0]);
        $this->assertArrayHasKey('password', $users[0]);
        $this->assertArrayHasKey('stat', $users[0]);
    }

    /**
     * @test
     *
     * @group database
     */
    public function testItCanSelectQueryWithLimitOffset()
    {
        $users = Query::from('users', $this->pdo)
            ->select()
            ->limitOffset(0, 10)
            ->all()
        ;

        $this->assertArrayHasKey('user', $users[0]);
        $this->assertArrayHasKey('password', $users[0]);
        $this->assertArrayHasKey('stat', $users[0]);
    }

    /**
     * @test
     *
     * @group database
     */
    public function testItCanSelectQueryWithStritMode()
    {
        $users = Query::from('users', $this->pdo)
            ->select()
            ->equal('user', 'taylor')
            ->equal('stat', 99)
            ->strictMode(false)
            ->all()
        ;

        $this->assertEquals('taylor', $users[0]['user']);
    }

    /**
     * @test
     *
     * @group database
     */
    public function testItCanSelectJoin()
    {
        $this->profileFactory();

        $users = Query::from('users', $this->pdo)
            ->select()
            ->equal('user', 'taylor')
            ->join(InnerJoin::ref('profiles', 'user '))
            ->all()
        ;

        $this->assertArrayHasKey('user', $users[0]);
        $this->assertArrayHasKey('password', $users[0]);
        $this->assertArrayHasKey('stat', $users[0]);
        $this->assertArrayHasKey('real_name', $users[0]);
    }
}
