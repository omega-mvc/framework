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
final class DeleteTest extends TestCase
{
    use ManagesDatabase;
    use UserTrait;

    protected function tearDown(): void
    {
        $this->dropConnection();

        parent::tearDown();
    }

    #[DataProvider('engineProvider')]
    public function testItCanDelete(string $engine): void
    {
        $this->createConnection($engine);
        $this->seedDefaultUser();

        Query::from('users', $this->pdo)
            ->delete()
            ->execute()
        ;

        $this->assertUserNotExist('taylor');
    }

    #[DataProvider('engineProvider')]
    public function testItCanDeleteWithBetween(string $engine): void
    {
        $this->createConnection($engine);
        $this->seedDefaultUser();

        Query::from('users', $this->pdo)
            ->delete()
            ->between('stat', 0, 100)
            ->execute()
        ;

        $this->assertUserNotExist('taylor');
    }

    #[DataProvider('engineProvider')]
    public function testItCanDeleteWithCompare(string $engine): void
    {
        $this->createConnection($engine);
        $this->seedDefaultUser();

        Query::from('users', $this->pdo)
            ->delete()
            ->compare('user', '=', 'taylor')
            ->execute()
        ;

        $this->assertUserNotExist('taylor');
    }

    #[DataProvider('engineProvider')]
    public function testItCanDeleteWithEqual(string $engine): void
    {
        $this->createConnection($engine);
        $this->seedDefaultUser();

        Query::from('users', $this->pdo)
            ->delete()
            ->equal('user', 'taylor')
            ->execute()
        ;

        $this->assertUserNotExist('taylor');
    }

    #[DataProvider('engineProvider')]
    public function testItCanDeleteWithIn(string $engine): void
    {
        $this->createConnection($engine);
        $this->seedDefaultUser();

        Query::from('users', $this->pdo)
            ->delete()
            ->in('user', ['taylor'])
            ->execute()
        ;

        $this->assertUserNotExist('taylor');
    }

    #[DataProvider('engineProvider')]
    public function testItCanDeleteWithLike(string $engine): void
    {
        $this->createConnection($engine);
        $this->seedDefaultUser();

        Query::from('users', $this->pdo)
            ->delete()
            ->like('user', 'tay%')
            ->execute()
        ;

        $this->assertUserNotExist('taylor');
    }

    #[DataProvider('engineProvider')]
    public function testItCanDeleteWithWhere(string $engine): void
    {
        $this->createConnection($engine);
        $this->seedDefaultUser();

        Query::from('users', $this->pdo)
            ->delete()
            ->where('user = :user', [
                [':user', 'taylor'],
            ])
            ->execute()
        ;

        $this->assertUserNotExist('taylor');
    }

    #[DataProvider('engineProvider')]
    public function testItCanDeleteWithMultyCondition(string $engine): void
    {
        $this->createConnection($engine);
        $this->seedDefaultUser();

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
