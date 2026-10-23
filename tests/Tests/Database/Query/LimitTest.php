<?php

declare(strict_types=1);

namespace Tests\Database\Query;

use Omega\Database\ConnectionInterface;
use Omega\Database\Query\Query;
use Omega\Database\Schema\SchemaConnection;
use Tests\TestCase;
use PHPUnit\Framework\Attributes\After;
use PHPUnit\Framework\Attributes\AfterClass;
use PHPUnit\Framework\Attributes\Before;
use PHPUnit\Framework\Attributes\BeforeClass;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;

final class LimitTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->pdo       = $this->createStub(ConnectionInterface::class);
        $this->pdoSchema = $this->createStub(SchemaConnection::class);
    }

    public function testItCorrectSelectQueryWithLimitOrder(): void
    {
        $select = Query::from('test', $this->pdo)
            ->select()
            ->between('column_1', 1, 100)
            ->limit(1, 10)
            ->order('column_1', Query::ORDER_ASC);

        $this->assertEquals(
            'SELECT * FROM test WHERE (test.column_1 BETWEEN :b_start AND :b_end) '
            . 'ORDER BY test.column_1 ASC LIMIT 1, 10',
            $select->__toString()
        );

        $this->assertEquals(
            'SELECT * FROM test WHERE (test.column_1 BETWEEN 1 AND 100) ORDER BY test.column_1 ASC LIMIT 1, 10',
            $select->queryBind()
        );
    }

    public function testItCorrectSelectQueryWithLimitEndOrderWithLimitEndLessThatZero(): void
    {
        $select = Query::from('test', $this->pdo)
            ->select()
            ->between('column_1', 1, 100)
            ->limit(2, -1)
            ->order('column_1', Query::ORDER_ASC);

        $this->assertEquals(
            'SELECT * FROM test WHERE (test.column_1 BETWEEN :b_start AND :b_end) '
            . 'ORDER BY test.column_1 ASC LIMIT 2, 0',
            $select->__toString()
        );

        $this->assertEquals(
            'SELECT * FROM test WHERE (test.column_1 BETWEEN 1 AND 100) ORDER BY test.column_1 ASC LIMIT 2, 0',
            $select->queryBind()
        );
    }

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

    public function testItCorrectSelectQueryWithLimitAndOffet(): void
    {
        $select = Query::from('test', $this->pdo)
            ->select()
            ->between('column_1', 1, 100)
            ->limitStart(1)
            ->offset(10)
            ->order('column_1', Query::ORDER_ASC);

        $this->assertEquals(
            'SELECT * FROM test WHERE (test.column_1 BETWEEN :b_start AND :b_end) '
            . 'ORDER BY test.column_1 ASC LIMIT 1 OFFSET 10',
            $select->__toString()
        );

        $this->assertEquals(
            'SELECT * FROM test WHERE (test.column_1 BETWEEN 1 AND 100) ORDER BY test.column_1 ASC LIMIT 1 OFFSET 10',
            $select->queryBind()
        );
    }

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
