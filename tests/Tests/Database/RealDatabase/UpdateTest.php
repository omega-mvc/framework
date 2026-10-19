<?php

declare(strict_types=1);

namespace Tests\Database\RealDatabase;

use Omega\Database\Query\Query;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Database\Asserts\UserTrait;
use Tests\Database\ManagesDatabase;
use Tests\TestCase;

#[CoversClass(Query::class)]
final class UpdateTest extends TestCase
{
    use ManagesDatabase;
    use UserTrait;

    protected function tearDown(): void
    {
        $this->dropConnection();

        parent::tearDown();
    }

    #[DataProvider('engineProvider')]
    public function testItCanUpdate(string $engine): void
    {
        $this->createConnection($engine);
        $this->seedDefaultUser();

        Query::from('users', $this->pdo)
            ->update()
            ->value('stat', 0)
            ->execute()
        ;

        $this->assertUserStat('taylor', 0);
    }

    #[DataProvider('engineProvider')]
    public function testItCanUpdateWithBetween(string $engine): void
    {
        $this->createConnection($engine);
        $this->seedDefaultUser();

        Query::from('users', $this->pdo)
            ->update()
            ->value('stat', 0)
            ->between('stat', 0, 100)
            ->execute()
        ;

        $this->assertUserStat('taylor', 0);
    }

    #[DataProvider('engineProvider')]
    public function testItCanUpdateWithCompare(string $engine): void
    {
        $this->createConnection($engine);
        $this->seedDefaultUser();

        Query::from('users', $this->pdo)
            ->update()
            ->value('stat', 0)
            ->compare('user', '=', 'taylor')
            ->execute()
        ;

        $this->assertUserStat('taylor', 0);
    }

    #[DataProvider('engineProvider')]
    public function testItCanUpdateWithEqual(string $engine): void
    {
        $this->createConnection($engine);
        $this->seedDefaultUser();

        Query::from('users', $this->pdo)
            ->update()
            ->value('stat', 0)
            ->equal('user', 'taylor')
            ->execute()
        ;

        $this->assertUserStat('taylor', 0);
    }

    #[DataProvider('engineProvider')]
    public function testItCanUpdateWithIn(string $engine): void
    {
        $this->createConnection($engine);
        $this->seedDefaultUser();

        Query::from('users', $this->pdo)
            ->update()
            ->value('stat', 0)
            ->in('user', ['taylor'])
            ->execute()
        ;

        $this->assertUserStat('taylor', 0);
    }

    #[DataProvider('engineProvider')]
    public function testItCanUpdateWithLike(string $engine): void
    {
        $this->createConnection($engine);
        $this->seedDefaultUser();

        Query::from('users', $this->pdo)
            ->update()
            ->value('stat', 0)
            ->like('user', 'tay%')
            ->execute()
        ;

        $this->assertUserStat('taylor', 0);
    }

    #[DataProvider('engineProvider')]
    public function testItCanUpdateWithWhere(string $engine): void
    {
        $this->createConnection($engine);
        $this->seedDefaultUser();

        Query::from('users', $this->pdo)
            ->update()
            ->value('stat', 0)
            ->where('user = :user', [
                [':user', 'taylor'],
            ])
            ->execute()
        ;

        $this->assertUserStat('taylor', 0);
    }

    #[DataProvider('engineProvider')]
    public function testItCanUpdateWithMultyCondition(string $engine): void
    {
        $this->createConnection($engine);
        $this->seedDefaultUser();

        Query::from('users', $this->pdo)
            ->update()
            ->value('stat', 0)
            ->compare('stat', '>', 1)
            ->where('user = :user', [
                [':user', 'taylor'],
            ])
            ->execute()
        ;

        $this->assertUserStat('taylor', 0);
    }
}
