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
final class InsertTest extends TestCase
{
    use ManagesDatabase;
    use UserTrait;

    protected function tearDown(): void
    {
        $this->dropConnection();

        parent::tearDown();
    }

    #[DataProvider('engineProvider')]
    public function testItCanInsertData(string $engine): void
    {
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

        $this->assertUserExist('sony');
    }

    #[DataProvider('engineProvider')]
    public function testItCanInsertMultyRaw(string $engine): void
    {
        $this->createConnection($engine);
        $this->seedDefaultUser();

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

    #[DataProvider('engineProvider')]
    public function testItCanReplaceOnExistData(string $engine): void
    {
        $this->requiresOneOf($engine, ['mysql', 'mariadb'], 'INSERT ON DUPLICATE KEY UPDATE');

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

    #[DataProvider('engineProvider')]
    public function testItCanUpdateInsertusingOneQuery(string $engine): void
    {
        $this->requiresOneOf($engine, ['mysql', 'mariadb'], 'INSERT ON DUPLICATE KEY UPDATE');

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
