<?php

declare(strict_types=1);

namespace Tests\Database\Query;

use Omega\Database\Query\Query;
use PHPUnit\Framework\Attributes\CoversClass;
use Tests\Database\TestDatabaseQuery;

#[CoversClass(Query::class)]
final class ReplaceTest extends TestDatabaseQuery
{
    /** @test */
    public function testItCorrectInsert(): void
    {
        $insert = Query::from('test', $this->pdo)
            ->replace()
            ->value('a', 1)
        ;

        $this->assertEquals(
            'REPLACE INTO test (a) VALUES (:bind_a)',
            $insert->__toString()
        );

        $this->assertEquals(
            'REPLACE INTO test (a) VALUES (1)',
            $insert->queryBind()
        );
    }

    /** @test */
    public function testItCorrectInsertValues(): void
    {
        $insert = Query::from('test', $this->pdo)
            ->replace()
            ->values([
                'a' => 'b',
                'c' => 'd',
                'e' => 'f',
            ])
        ;

        $this->assertEquals(
            'REPLACE INTO test (a, c, e) VALUES (:bind_a, :bind_c, :bind_e)',
            $insert->__toString()
        );

        $this->assertEquals(
            "REPLACE INTO test (a, c, e) VALUES ('b', 'd', 'f')",
            $insert->queryBind()
        );
    }

    /** @test */
    public function testItCorrectInsertQueryMultyValues(): void
    {
        $insert = Query::from('test', $this->pdo)
            ->replace()
            ->values([
                'a' => 'b',
                'c' => 'd',
                'e' => 'f',
            ])
            ->value('g', 'h')
        ;

        $this->assertEquals(
            'REPLACE INTO test (a, c, e, g) VALUES (:bind_a, :bind_c, :bind_e, :bind_g)',
            $insert->__toString()
        );

        $this->assertEquals(
            "REPLACE INTO test (a, c, e, g) VALUES ('b', 'd', 'f', 'h')",
            $insert->queryBind()
        );
    }

    /** @test */
    public function testItCorrectInsertQueryMultyRaws(): void
    {
        $insert = Query::from('test', $this->pdo)
            ->replace()
            ->rows([
                [
                    'a' => 'b',
                    'c' => 'd',
                    'e' => 'f',
                ], [
                    'a' => 'b',
                    'c' => 'd',
                    'e' => 'f',
                ],
            ]);

        $this->assertEquals(
            'REPLACE INTO test (a, c, e) VALUES (:bind_0_a, :bind_0_c, :bind_0_e), (:bind_1_a, :bind_1_c, :bind_1_e)',
            $insert->__toString()
        );

        $this->assertEquals(
            "REPLACE INTO test (a, c, e) VALUES ('b', 'd', 'f'), ('b', 'd', 'f')",
            $insert->queryBind()
        );
    }
}
