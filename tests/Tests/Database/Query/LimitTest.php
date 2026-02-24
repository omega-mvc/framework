<?php

declare(strict_types=1);

namespace Tests\Database\Query;

use Omega\Database\Query\Query;
use PHPUnit\Framework\Attributes\CoversClass;
use Tests\Database\TestDatabaseQuery;

#[CoversClass(Query::class)]
final class LimitTest extends TestDatabaseQuery
{
    /** @test */
    public function testItCorrectSelectQueryWithLimitOrder(): void
    {
        $select = Query::from('test', $this->pdo)
            ->select()
            ->between('column_1', 1, 100)
            ->limit(1, 10)
            ->order('column_1', Query::ORDER_ASC);

        $this->assertEquals(
            'SELECT * FROM test WHERE (test.column_1 BETWEEN :b_start AND :b_end) ORDER BY test.column_1 ASC LIMIT 1, 10',
            $select->__toString(),
            'select with where statment is between'
        );

        $this->assertEquals(
            'SELECT * FROM test WHERE (test.column_1 BETWEEN 1 AND 100) ORDER BY test.column_1 ASC LIMIT 1, 10',
            $select->queryBind(),
            'select with where statment is between'
        );
    }

    /** @test */
    public function testItCorrectSelectQueryWithLimitEndOrderWIthLimitEndLessThatZero(): void
    {
        $select = Query::from('test', $this->pdo)
            ->select()
            ->between('column_1', 1, 100)
            ->limit(2, -1)
            ->order('column_1', Query::ORDER_ASC);

        $this->assertEquals(
            'SELECT * FROM test WHERE (test.column_1 BETWEEN :b_start AND :b_end) ORDER BY test.column_1 ASC LIMIT 2, 0',
            $select->__toString(),
            'select with where statment is between'
        );

        $this->assertEquals(
            'SELECT * FROM test WHERE (test.column_1 BETWEEN 1 AND 100) ORDER BY test.column_1 ASC LIMIT 2, 0',
            $select->queryBind(),
            'select with where statment is between'
        );
    }

    /** @test */
    public function testItCorrectSelectQueryWithLimitStartLessThatZero(): void
    {
        $select = Query::from('test', $this->pdo)
            ->select()
            ->between('column_1', 1, 100)
            ->limit(-1, 2)
            ->order('column_1', Query::ORDER_ASC);

        $this->assertEquals(
            'SELECT * FROM test WHERE (test.column_1 BETWEEN :b_start AND :b_end) ORDER BY test.column_1 ASC LIMIT 2',
            $select->__toString()
        );

        $this->assertEquals(
            'SELECT * FROM test WHERE (test.column_1 BETWEEN 1 AND 100) ORDER BY test.column_1 ASC LIMIT 2',
            $select->queryBind()
        );
    }

    /** @test */
    public function testItCorrectSelectQueryWithLimitAndOffet(): void
    {
        $select = Query::from('test', $this->pdo)
            ->select()
            ->between('column_1', 1, 100)
            ->limitStart(1)
            ->offset(10)
            ->order('column_1', Query::ORDER_ASC);

        $this->assertEquals(
            'SELECT * FROM test WHERE (test.column_1 BETWEEN :b_start AND :b_end) ORDER BY test.column_1 ASC LIMIT 1 OFFSET 10',
            $select->__toString()
        );

        $this->assertEquals(
            'SELECT * FROM test WHERE (test.column_1 BETWEEN 1 AND 100) ORDER BY test.column_1 ASC LIMIT 1 OFFSET 10',
            $select->queryBind()
        );
    }

    /** @test */
    public function testItCorrectSelectQueryWithLimitStartAndLimitEndtLessThatZero(): void
    {
        $select = Query::from('test', $this->pdo)
            ->select()
            ->between('column_1', 1, 100)
            ->limit(-1, -1)
            ->order('column_1', Query::ORDER_ASC);

        $this->assertEquals(
            'SELECT * FROM test WHERE (test.column_1 BETWEEN :b_start AND :b_end) ORDER BY test.column_1 ASC',
            $select->__toString()
        );
        $this->assertEquals(
            'SELECT * FROM test WHERE (test.column_1 BETWEEN :b_start AND :b_end) ORDER BY test.column_1 ASC',
            $select->__toString()
        );
    }
}
