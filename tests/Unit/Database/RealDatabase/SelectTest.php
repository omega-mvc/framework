<?php

declare(strict_types=1);

namespace Tests\Database\RealDatabase;

use Omega\Database\Query\Join\InnerJoin;
use Omega\Database\Query\Query;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Database\Asserts\UserTrait;
use Tests\Database\ManagesDatabase;
use Tests\TestCase;

#[CoversClass(Query::class)]
#[CoversClass(InnerJoin::class)]
final class SelectTest extends TestCase
{
    use ManagesDatabase;
    use UserTrait;

    protected function tearDown(): void
    {
        $this->dropConnection();

        parent::tearDown();
    }

    #[DataProvider('engineProvider')]
    public function testItCanSelectQuery(string $engine): void
    {
        $this->createConnection($engine);
        $this->seedDefaultUser();

        $users = Query::from('users', $this->pdo)
            ->select()
            ->all() ?: []
        ;

        $this->assertArrayHasKey('user', $users[0]);
        $this->assertArrayHasKey('password', $users[0]);
        $this->assertArrayHasKey('stat', $users[0]);
    }

    #[DataProvider('engineProvider')]
    public function testItCanSelectQueryOnlyuser(string $engine): void
    {
        $this->createConnection($engine);
        $this->seedDefaultUser();

        $users = Query::from('users', $this->pdo)
            ->select(['user'])
            ->all() ?: []
        ;

        $this->assertArrayHasKey('user', $users[0]);
        $this->assertArrayNotHasKey('password', $users[0]);
        $this->assertArrayNotHasKey('stat', $users[0]);
    }

    #[DataProvider('engineProvider')]
    public function testItCanSelectQueryWithBetween(string $engine): void
    {
        $this->createConnection($engine);
        $this->seedDefaultUser();

        $users = Query::from('users', $this->pdo)
            ->select()
            ->between('stat', 0, 100)
            ->all() ?: []
        ;

        $this->assertEquals('taylor', $users[0]['user']);
    }

    #[DataProvider('engineProvider')]
    public function testItCanSelectQueryWithCompare(string $engine): void
    {
        $this->createConnection($engine);
        $this->seedDefaultUser();

        $users = Query::from('users', $this->pdo)
            ->select()
            ->compare('user', '=', 'taylor')
            ->all() ?: []
        ;

        $this->assertEquals('taylor', $users[0]['user']);
    }

    #[DataProvider('engineProvider')]
    public function testItCanSelectQueryWithEqual(string $engine): void
    {
        $this->createConnection($engine);
        $this->seedDefaultUser();

        $users = Query::from('users', $this->pdo)
            ->select()
            ->equal('user', 'taylor')
            ->all() ?: []
        ;

        $this->assertEquals('taylor', $users[0]['user']);
    }

    #[DataProvider('engineProvider')]
    public function testItCanSelectQueryWithIn(string $engine): void
    {
        $this->createConnection($engine);
        $this->seedDefaultUser();

        $users = Query::from('users', $this->pdo)
            ->select()
            ->in('user', ['taylor'])
            ->all() ?: []
        ;

        $this->assertEquals('taylor', $users[0]['user']);
    }

    #[DataProvider('engineProvider')]
    public function testItCanSelectQueryWithLike(string $engine): void
    {
        $this->createConnection($engine);
        $this->seedDefaultUser();

        $users = Query::from('users', $this->pdo)
            ->select()
            ->like('user', 'tay%')
            ->all() ?: []
        ;

        $this->assertEquals('taylor', $users[0]['user']);
    }

    #[DataProvider('engineProvider')]
    public function testItCanSelectQueryWithWhere(string $engine): void
    {
        $this->createConnection($engine);
        $this->seedDefaultUser();

        $users = Query::from('users', $this->pdo)
            ->select()
            ->where('user = :user', [
                [':user', 'taylor'],
            ])
            ->all() ?: []
        ;

        $this->assertEquals('taylor', $users[0]['user']);
    }

    #[DataProvider('engineProvider')]
    public function testItCanSelectQueryWithMultyCondition(string $engine): void
    {
        $this->createConnection($engine);
        $this->seedDefaultUser();

        $users = Query::from('users', $this->pdo)
            ->select()
            ->compare('stat', '>', 1)
            ->where('user = :user', [
                [':user', 'taylor'],
            ])
            ->all() ?: []
        ;

        $this->assertEquals('taylor', $users[0]['user']);
    }

    #[DataProvider('engineProvider')]
    public function testItCanSelectQueryWithLimit(string $engine): void
    {
        $this->createConnection($engine);
        $this->seedDefaultUser();

        $users = Query::from('users', $this->pdo)
            ->select()
            ->limit(0, 1)
            ->all() ?: []
        ;

        $this->assertArrayHasKey('user', $users[0]);
        $this->assertArrayHasKey('password', $users[0]);
        $this->assertArrayHasKey('stat', $users[0]);
    }

    #[DataProvider('engineProvider')]
    public function testItCanSelectQueryWithOffset(string $engine): void
    {
        $this->createConnection($engine);
        $this->seedDefaultUser();

        $users = Query::from('users', $this->pdo)
            ->select()
            ->limitStart(0)
            ->offset(1)
            ->all() ?: []
        ;

        $this->assertArrayHasKey('user', $users[0]);
        $this->assertArrayHasKey('password', $users[0]);
        $this->assertArrayHasKey('stat', $users[0]);
    }

    #[DataProvider('engineProvider')]
    public function testItCanSelectQueryWithLimitOffset(string $engine): void
    {
        $this->createConnection($engine);
        $this->seedDefaultUser();

        $users = Query::from('users', $this->pdo)
            ->select()
            ->limitOffset(0, 10)
            ->all() ?: []
        ;

        $this->assertArrayHasKey('user', $users[0]);
        $this->assertArrayHasKey('password', $users[0]);
        $this->assertArrayHasKey('stat', $users[0]);
    }

    #[DataProvider('engineProvider')]
    public function testItCanSelectQueryWithStritMode(string $engine): void
    {
        $this->createConnection($engine);
        $this->seedDefaultUser();

        $users = Query::from('users', $this->pdo)
            ->select()
            ->equal('user', 'taylor')
            ->equal('stat', 99)
            ->strictMode(false)
            ->all() ?: []
        ;

        $this->assertEquals('taylor', $users[0]['user']);
    }

    #[DataProvider('engineProvider')]
    public function testItCanSelectJoin(string $engine): void
    {
        $this->createConnection($engine);
        $this->seedDefaultUser();

        $this->pdo
            ->query('CREATE TABLE profiles (
                user varchar(32) NOT NULL,
                real_name varchar(500) NOT NULL,
                PRIMARY KEY (user)
              )')
            ->execute()
        ;

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
            ->execute()
        ;

        $users = Query::from('users', $this->pdo)
            ->select()
            ->equal('user', 'taylor')
            ->join(InnerJoin::ref('profiles', 'user '))
            ->all() ?: []
        ;

        $this->assertArrayHasKey('user', $users[0]);
        $this->assertArrayHasKey('password', $users[0]);
        $this->assertArrayHasKey('stat', $users[0]);
        $this->assertArrayHasKey('real_name', $users[0]);
    }
}
