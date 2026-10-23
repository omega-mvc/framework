<?php

declare(strict_types=1);

namespace Tests\Database\Query;

use Omega\Database\ConnectionInterface;
use Omega\Database\Query\InnerQuery;
use Omega\Database\Query\Query;
use Omega\Database\Query\Select;
use Omega\Database\Schema\SchemaConnection;
use Tests\TestCase;
use PHPUnit\Framework\Attributes\After;
use PHPUnit\Framework\Attributes\AfterClass;
use PHPUnit\Framework\Attributes\Before;
use PHPUnit\Framework\Attributes\BeforeClass;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;

final class SelectTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->pdo       = $this->createStub(ConnectionInterface::class);
        $this->pdoSchema = $this->createStub(SchemaConnection::class);
    }

    public function testItCanSelectBetween(): void
    {
        $select = Query::from('test', $this->pdo)
            ->select()
            ->between('column_1', 1, 100)
        ;

        $this->assertEquals(
            'SELECT * FROM test WHERE (test.column_1 BETWEEN :b_start AND :b_end)',
            $select->__toString()
        );

        $this->assertEquals(
            'SELECT * FROM test WHERE (test.column_1 BETWEEN 1 AND 100)',
            $select->queryBind()
        );
    }

    public function testItCanSelectCompare(): void
    {
        $select = Query::from('test', $this->pdo)
            ->select()
            ->compare('column_1', '=', 100)
        ;

        $this->assertEquals(
            'SELECT * FROM test WHERE ( (test.column_1 = :column_1) )',
            $select->__toString()
        );

        $this->assertEquals(
            'SELECT * FROM test WHERE ( (test.column_1 = 100) )',
            $select->queryBind()
        );
    }

    public function testItCanSelectEqual(): void
    {
        $select = Query::from('test', $this->pdo)
            ->select()
            ->equal('column_1', 100)
        ;

        $this->assertEquals(
            'SELECT * FROM test WHERE ( (test.column_1 = :column_1) )',
            $select->__toString()
        );

        $this->assertEquals(
            'SELECT * FROM test WHERE ( (test.column_1 = 100) )',
            $select->queryBind()
        );
    }

    public function testItCanSelectIn(): void
    {
        $select = Query::from('test', $this->pdo)
            ->select()
            ->in('column_1', [1, 2])
        ;

        $this->assertEquals(
            'SELECT * FROM test WHERE (test.column_1 IN (:in_0, :in_1))',
            $select->__toString()
        );

        $this->assertEquals(
            'SELECT * FROM test WHERE (test.column_1 IN (1, 2))',
            $select->queryBind()
        );
    }

    public function testItCanSelectLike(): void
    {
        $select = Query::from('test', $this->pdo)
            ->select()
            ->like('column_1', 'test')
        ;

        $this->assertEquals(
            'SELECT * FROM test WHERE ( (test.column_1 LIKE :column_1) )',
            $select->__toString()
        );

        $this->assertEquals(
            "SELECT * FROM test WHERE ( (test.column_1 LIKE 'test') )",
            $select->queryBind()
        );
    }

    public function testItCanSelectWhere(): void
    {
        $select = Query::from('test', $this->pdo)
            ->select()
            ->where('a < :a OR b > :b', [[':a', 1], [':b', 2]])
        ;

        $this->assertEquals(
            'SELECT * FROM test WHERE a < :a OR b > :b',
            $select->__toString()
        );

        $this->assertEquals(
            'SELECT * FROM test WHERE a < 1 OR b > 2',
            $select->queryBind()
        );
    }

    public function testItCorrectSelectMultyColumn(): void
    {
        $select = Query::from('test', $this->pdo)
            ->select(['column_1', 'column_2', 'column_3'])
            ->equal('column_1', 123)
            ->equal('column_2', 'abc')
            ->equal('column_3', true);

        $this->assertEquals(
            'SELECT column_1, column_2, column_3 FROM test WHERE ( (test.column_1 = :column_1) '
            . 'AND (test.column_2 = :column_2) AND (test.column_3 = :column_3) )',
            $select->__toString()
        );

        $this->assertEquals(
            "SELECT column_1, column_2, column_3 FROM test WHERE ( (test.column_1 = 123) "
            . "AND (test.column_2 = 'abc') AND (test.column_3 = true) )",
            $select->queryBind()
        );
    }

    public function testItCorrectSelectWithStrictOff(): void
    {
        $select = Query::from('test', $this->pdo)
            ->select(['column_1', 'column_2', 'column_3'])
            ->equal('column_1', 123)
            ->equal('column_2', 'abc')
            ->strictMode(false);

        $this->assertEquals(
            'SELECT column_1, column_2, column_3 FROM test WHERE ( (test.column_1 = :column_1) '
            . 'OR (test.column_2 = :column_2) )',
            $select->__toString()
        );

        $this->assertEquals(
            "SELECT column_1, column_2, column_3 FROM test WHERE ( (test.column_1 = 123) OR (test.column_2 = 'abc') )",
            $select->queryBind()
        );
    }

    public function testItCanGenerateWhereExisQuery(): void
    {
        $select = Query::from('base_1', $this->pdo)
            ->select()
            ->whereExist(
                (new Select('base_2', ['*'], $this->pdo))
                    ->equal('test', 'success')
                    ->where('base_1.id = base_2.id')
            )
            ->limit(1, 10)
            ->order('id', Query::ORDER_ASC)
        ;

        $this->assertEquals(
            'SELECT * FROM base_1 WHERE EXISTS ( SELECT * FROM base_2 WHERE ( (base_2.test = :test) ) '
            . 'AND base_1.id = base_2.id ) ORDER BY base_1.id ASC LIMIT 1, 10',
            $select->__toString()
        );

        $this->assertEquals(
            "SELECT * FROM base_1 WHERE EXISTS ( SELECT * FROM base_2 WHERE ( (base_2.test = 'success') ) "
            . "AND base_1.id = base_2.id ) ORDER BY base_1.id ASC LIMIT 1, 10",
            $select->queryBind()
        );
    }

    public function testItCanGenerateWhereNotExisQuery(): void
    {
        $select = Query::from('base_1', $this->pdo)
            ->select()
            ->whereNotExist(
                (new Select('base_2', ['*'], $this->pdo))
                    ->equal('test', 'success')
                    ->where('base_1.id = base_2.id')
            )
            ->limit(1, 10)
            ->order('id', Query::ORDER_ASC)
        ;

        $this->assertEquals(
            'SELECT * FROM base_1 WHERE NOT EXISTS ( SELECT * FROM base_2 WHERE ( (base_2.test = :test) ) '
            . 'AND base_1.id = base_2.id ) ORDER BY base_1.id ASC LIMIT 1, 10',
            $select->__toString()
        );

        $this->assertEquals(
            "SELECT * FROM base_1 WHERE NOT EXISTS ( SELECT * FROM base_2 WHERE ( (base_2.test = 'success') ) "
            . "AND base_1.id = base_2.id ) ORDER BY base_1.id ASC LIMIT 1, 10",
            $select->queryBind()
        );
    }

    public function testItCanGenerateSelectWithWhereQuery(): void
    {
        $select = Query::from('base_1', $this->pdo)
            ->select()
            ->whereClause(
                'user =',
                (new Select('base_2', ['*'], $this->pdo))
                    ->equal('test', 'success')
                    ->where('base_1.id = base_2.id')
            )
            ->limit(1, 10)
            ->order('id', Query::ORDER_ASC)
        ;

        $this->assertEquals(
            'SELECT * FROM base_1 WHERE user = ( SELECT * FROM base_2 WHERE ( (base_2.test = :test) ) '
            . 'AND base_1.id = base_2.id ) ORDER BY base_1.id ASC LIMIT 1, 10',
            $select->__toString()
        );

        $this->assertEquals(
            "SELECT * FROM base_1 WHERE user = ( SELECT * FROM base_2 WHERE ( (base_2.test = 'success') ) "
            . "AND base_1.id = base_2.id ) ORDER BY base_1.id ASC LIMIT 1, 10",
            $select->queryBind()
        );
    }

    public function testItCanGenerateSelectWithSubQuery(): void
    {
        $select = Query::from(
            new InnerQuery(
                (new Select('base_2', ['id'], $this->pdo))
                    ->in('test', ['success']),
                'user'
            ),
            $this->pdo
        )
            ->select(['user.id as id'])
            ->limit(1, 10)
            ->order('id', Query::ORDER_ASC)
        ;

        $this->assertEquals(
            'SELECT user.id as id FROM (SELECT id FROM base_2 WHERE (base_2.test IN (:in_0))) '
            . 'AS user ORDER BY user.id ASC LIMIT 1, 10',
            $select->__toString()
        );

        $this->assertEquals(
            "SELECT user.id as id FROM (SELECT id FROM base_2 WHERE (base_2.test IN ('success'))) "
            . "AS user ORDER BY user.id ASC LIMIT 1, 10",
            $select->queryBind()
        );
    }

    public function testItCanSelectWithGroupBy(): void
    {
        $select = Query::from('test', $this->pdo)
            ->select()
            ->groupBy('culumn_1')
        ;
        $select_multy = Query::from('test', $this->pdo)
            ->select()
            ->groupBy('culumn_1', 'column_2')
        ;

        $this->assertEquals(
            'SELECT * FROM test GROUP BY culumn_1',
            $select->__toString()
        );

        $this->assertEquals(
            'SELECT * FROM test GROUP BY culumn_1, column_2',
            $select_multy->__toString()
        );
    }

    public function testItCanGenerateMultyOrder(): void
    {
        $select = Query::from('base_1', $this->pdo)
            ->select()
            ->order('id', Query::ORDER_ASC)
            ->order('name', Query::ORDER_DESC)
        ;

        $this->assertEquals(
            'SELECT * FROM base_1 ORDER BY base_1.id ASC, base_1.name DESC',
            $select->__toString()
        );
    }

    public function testItCanSelectWithOrderIfNotNull(): void
    {
        $select = Query::from('test', $this->pdo)
            ->select()
            ->orderIfNotNull('column_1');

        $this->assertEquals(
            'SELECT * FROM test ORDER BY test.column_1 IS NOT NULL ASC',
            $select->__toString()
        );
    }

    public function testItCanSelectWithOrderIfNull(): void
    {
        $select = Query::from('test', $this->pdo)
            ->select()
            ->orderIfNull('column_1');

        $this->assertEquals(
            'SELECT * FROM test ORDER BY test.column_1 IS NULL ASC',
            $select->__toString()
        );
    }
}
