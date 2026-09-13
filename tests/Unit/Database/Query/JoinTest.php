<?php

declare(strict_types=1);

namespace Tests\Database\Query;

use Omega\Database\ConnectionInterface;
use Omega\Database\Query\InnerQuery;
use Omega\Database\Query\Join\CrossJoin;
use Omega\Database\Query\Join\FullJoin;
use Omega\Database\Query\Join\InnerJoin;
use Omega\Database\Query\Join\LeftJoin;
use Omega\Database\Query\Join\RightJoin;
use Omega\Database\Query\Query;
use Omega\Database\Query\Select;
use Omega\Database\Schema\SchemaConnection;

covers(Query::class);
covers(InnerQuery::class);
covers(CrossJoin::class);
covers(FullJoin::class);
covers(InnerJoin::class);
covers(LeftJoin::class);
covers(RightJoin::class);
covers(Select::class);

beforeEach(function (): void {
    $this->pdo       = $this->createStub(ConnectionInterface::class);
    $this->pdoSchema = $this->createStub(SchemaConnection::class);
});

test('it can generate inner join', function (): void {
    $join = Query::from('base_table', $this->pdo)
        ->select()
        ->join(InnerJoin::ref('join_table', 'base_id', 'join_id'))
    ;

    expect($join->__toString())->toEqual(
        'SELECT * FROM base_table INNER JOIN join_table ON base_table.base_id = join_table.join_id'
    );

    expect($join->queryBind())->toEqual(
        'SELECT * FROM base_table INNER JOIN join_table ON base_table.base_id = join_table.join_id'
    );
});

test('it can generate left join', function (): void {
    $join = Query::from('base_table', $this->pdo)
        ->select()
        ->join(LeftJoin::ref('join_table', 'base_id', 'join_id'))
    ;

    expect($join->__toString())->toEqual(
        'SELECT * FROM base_table LEFT JOIN join_table ON base_table.base_id = join_table.join_id'
    );

    expect($join->queryBind())->toEqual(
        'SELECT * FROM base_table LEFT JOIN join_table ON base_table.base_id = join_table.join_id'
    );
});

test('it can generate right join', function (): void {
    $join = Query::from('base_table', $this->pdo)
        ->select()
        ->join(RightJoin::ref('join_table', 'base_id', 'join_id'))
    ;

    expect($join->__toString())->toEqual(
        'SELECT * FROM base_table RIGHT JOIN join_table ON base_table.base_id = join_table.join_id'
    );

    expect($join->queryBind())->toEqual(
        'SELECT * FROM base_table RIGHT JOIN join_table ON base_table.base_id = join_table.join_id'
    );
});

test('it can generate full join', function (): void {
    $join = Query::from('base_table', $this->pdo)
        ->select()
        ->join(FullJoin::ref('join_table', 'base_id', 'join_id'))
    ;

    expect($join->__toString())->toEqual(
        'SELECT * FROM base_table FULL OUTER JOIN join_table ON base_table.base_id = join_table.join_id'
    );

    expect($join->queryBind())->toEqual(
        'SELECT * FROM base_table FULL OUTER JOIN join_table ON base_table.base_id = join_table.join_id'
    );
});

test('it can generate cross join', function (): void {
    $join = Query::from('base_table', $this->pdo)
        ->select()
        ->join(CrossJoin::ref('join_table', 'base_id', 'join_id'))
    ;

    expect($join->__toString())->toEqual(
        'SELECT * FROM base_table CROSS JOIN join_table'
    );

    expect($join->queryBind())->toEqual(
        'SELECT * FROM base_table CROSS JOIN join_table'
    );
});

test('it can join multyple', function (): void {
    $join = Query::from('base_table', $this->pdo)
        ->select()
        ->join(InnerJoin::ref('join_table_1', 'base_id', 'join_id'))
        ->join(InnerJoin::ref('join_table_2', 'base_id', 'join_id'))
    ;

    expect($join->__toString())->toEqual(
        'SELECT * FROM base_table INNER JOIN join_table_1 ON base_table.base_id = join_table_1.join_id '
        . 'INNER JOIN join_table_2 ON base_table.base_id = join_table_2.join_id'
    );

    expect($join->queryBind())->toEqual(
        'SELECT * FROM base_table INNER JOIN join_table_1 ON base_table.base_id = join_table_1.join_id '
        . 'INNER JOIN join_table_2 ON base_table.base_id = join_table_2.join_id'
    );
});

test('it can join with condition', function (): void {
    $join = Query::from('base_table', $this->pdo)
        ->select()
        ->equal('a', 1)
        ->join(InnerJoin::ref('join_table_1', 'base_id', 'join_id'))
    ;

    expect($join->__toString())->toEqual(
        'SELECT * FROM base_table INNER JOIN join_table_1 ON base_table.base_id = join_table_1.join_id '
        . 'WHERE ( (base_table.a = :a) )'
    );

    expect($join->queryBind())->toEqual(
        'SELECT * FROM base_table INNER JOIN join_table_1 ON base_table.base_id = join_table_1.join_id '
        . 'WHERE ( (base_table.a = 1) )'
    );
});

test('it can generate inner join with sub query', function (): void {
    $join = Query::from('base_table', $this->pdo)
        ->select()
        ->join(InnerJoin::ref(
            new InnerQuery(
                (new Select('join_table', ['join_id'], $this->pdo))->in('join_id', [1, 2]),
                'join_table'
            ),
            'base_id',
            'join_id'
        ))
        ->order('base_id')
    ;

    expect($join->__toString())->toEqual(
        'SELECT * FROM base_table INNER JOIN (SELECT join_id FROM join_table '
        . 'WHERE (join_table.join_id IN (:in_0, :in_1))) AS join_table ON base_table.base_id = join_table.join_id '
        . 'ORDER BY base_table.base_id ASC'
    );

    expect($join->queryBind())->toEqual(
        'SELECT * FROM base_table INNER JOIN (SELECT join_id FROM join_table '
        . 'WHERE (join_table.join_id IN (1, 2))) AS join_table ON base_table.base_id = join_table.join_id '
        . 'ORDER BY base_table.base_id ASC'
    );
});

test('it can generate inner join in delete clausa', function (): void {
    $join = Query::from('base_table', $this->pdo)
        ->delete()
        ->alias('bt')
        ->join(InnerJoin::ref('join_table', 'base_id', 'join_id'))
        ->equal('join_table.a', 1)
    ;

    expect($join->__toString())->toEqual(
        'DELETE bt FROM base_table AS bt INNER JOIN join_table ON bt.base_id = join_table.join_id '
        . 'WHERE ( (join_table.a = :join_table__a) )'
    );

    expect($join->queryBind())->toEqual(
        'DELETE bt FROM base_table AS bt INNER JOIN join_table ON bt.base_id = join_table.join_id '
        . 'WHERE ( (join_table.a = 1) )'
    );
});

test('it can generate inner join in update clausa', function (): void {
    $update = Query::from('test', $this->pdo)
        ->update()
        ->value('a', 'b')
        ->join(InnerJoin::ref('join_table', 'base_id', 'join_id'))
        ->equal('test.column_1', 100)
    ;

    expect($update->__toString())->toEqual(
        'UPDATE test INNER JOIN join_table ON test.base_id = join_table.join_id SET a = :bind_a '
        . 'WHERE ( (test.column_1 = :test__column_1) )'
    );

    expect($update->queryBind())->toEqual(
        'UPDATE test INNER JOIN join_table ON test.base_id = join_table.join_id SET a = \'b\' '
        . 'WHERE ( (test.column_1 = 100) )'
    );
});