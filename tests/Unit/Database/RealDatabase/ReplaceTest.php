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
final class ReplaceTest extends TestCase
{
    use ManagesDatabase;
    use UserTrait;

    protected function tearDown(): void
    {
        $this->dropConnection();

        parent::tearDown();
    }

    #[DataProvider('engineProvider')]
    public function testItCanReplaceOnNewData(string $engine): void
    {
        $this->requiresOneOf($engine, ['mysql', 'mariadb', 'sqlite'], 'REPLACE INTO statement');

        $this->createConnection($engine);
        $this->seedDefaultUser();

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

    #[DataProvider('engineProvider')]
    public function testItCanReplaceOnExistData(string $engine): void
    {
        $this->requiresOneOf($engine, ['mysql', 'mariadb', 'sqlite'], 'REPLACE INTO statement');

        $this->createConnection($engine);
        $this->seedDefaultUser();

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

    #[DataProvider('engineProvider')]
    public function testItCanUpdateInsertusingOneQuery(string $engine): void
    {
        $this->requiresOneOf($engine, ['mysql', 'mariadb', 'sqlite'], 'REPLACE INTO statement');

        $this->createConnection($engine);
        $this->seedDefaultUser();

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
