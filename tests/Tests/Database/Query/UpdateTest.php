<?php

declare(strict_types=1);

namespace Tests\Database\Query;

use Omega\Database\Query\Query;
use Tests\Database\TestDatabaseQuery;

final class UpdateTest extends TestDatabaseQuery
{
    /** @test */
    public function testItCanUpdateBetween()
    {
        $update = Query::from('test', $this->pdo)
            ->update()
            ->value('a', 'b')
            ->between('column_1', 1, 100)
        ;

        $this->assertEquals(
            'UPDATE test SET a = :bind_a WHERE (test.column_1 BETWEEN :b_start AND :b_end)',
            $update->__toString()
        );

        $this->assertEquals(
            "UPDATE test SET a = 'b' WHERE (test.column_1 BETWEEN 1 AND 100)",
            $update->queryBind()
        );
    }

    /** @test */
    public function testItCanUpdateCompare()
    {
        $update = Query::from('test', $this->pdo)
            ->update()
            ->value('a', 'b')
            ->compare('column_1', '=', 100)
        ;

        $this->assertEquals(
            'UPDATE test SET a = :bind_a WHERE ( (test.column_1 = :column_1) )',
            $update->__toString()
        );

        $this->assertEquals(
            "UPDATE test SET a = 'b' WHERE ( (test.column_1 = 100) )",
            $update->queryBind()
        );
    }

    /** @test */
    public function testItCanUpdateEqual()
    {
        $update = Query::from('test', $this->pdo)
            ->update()
            ->value('a', 'b')
            ->equal('column_1', 100)
        ;

        $this->assertEquals(
            'UPDATE test SET a = :bind_a WHERE ( (test.column_1 = :column_1) )',
            $update->__toString()
        );

        $this->assertEquals(
            "UPDATE test SET a = 'b' WHERE ( (test.column_1 = 100) )",
            $update->queryBind()
        );
    }

    /** @test */
    public function testItCanUpdateIn()
    {
        $update = Query::from('test', $this->pdo)
            ->update()
            ->value('a', 'b')
            ->in('column_1', [1, 2])
        ;

        $this->assertEquals(
            'UPDATE test SET a = :bind_a WHERE (test.column_1 IN (:in_0, :in_1))',
            $update->__toString()
        );

        $this->assertEquals(
            "UPDATE test SET a = 'b' WHERE (test.column_1 IN (1, 2))",
            $update->queryBind()
        );
    }

    /** @test */
    public function testItCanUpdateLike()
    {
        $update = Query::from('test', $this->pdo)
            ->update()
            ->value('a', 'b')
            ->like('column_1', 'test')
        ;

        $this->assertEquals(
            'UPDATE test SET a = :bind_a WHERE ( (test.column_1 LIKE :column_1) )',
            $update->__toString()
        );

        $this->assertEquals(
            "UPDATE test SET a = 'b' WHERE ( (test.column_1 LIKE 'test') )",
            $update->queryBind()
        );
    }

    /** @test */
    public function testItCanUpdateWhere()
    {
        $update = Query::from('test', $this->pdo)
            ->update()
            ->value('a', 'b')
            ->where('a < :a OR b > :b', [[':a', 1], [':b', 2]])
        ;

        $this->assertEquals(
            'UPDATE test SET a = :bind_a WHERE a < :a OR b > :b',
            $update->__toString(),
            'update with where statment is like'
        );

        $this->assertEquals(
            "UPDATE test SET a = 'b' WHERE a < 1 OR b > 2",
            $update->queryBind(),
            'update with where statment is like'
        );
    }

    /** @test */
    public function testItCorrectUpdateWithStrictOff(): void
    {
        $update = Query::from('test', $this->pdo)
            ->update()
            ->value('a', 'b')
            ->equal('column_1', 123)
            ->equal('column_2', 'abc')
            ->strictMode(false);

        $this->assertEquals(
            'UPDATE test SET a = :bind_a WHERE ( (test.column_1 = :column_1) OR (test.column_2 = :column_2) )',
            $update->__toString()
        );

        $this->assertEquals(
            "UPDATE test SET a = 'b' WHERE ( (test.column_1 = 123) OR (test.column_2 = 'abc') )",
            $update->queryBind()
        );
    }
}
