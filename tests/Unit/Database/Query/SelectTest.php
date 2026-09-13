<?php

declare(strict_types=1);

namespace Tests\Database\Query;

use Omega\Database\ConnectionInterface;
use Omega\Database\Query\InnerQuery;
use Omega\Database\Query\Query;
use Omega\Database\Query\Select;
use Omega\Database\Schema\SchemaConnection;

covers(Query::class);
covers(InnerQuery::class);
covers(Select::class);

beforeEach(function (): void {
    $this->pdo       = $this->createStub(ConnectionInterface::class);
    $this->pdoSchema = $this->createStub(SchemaConnection::class);
});

test('it can select between', function (): void {
    $select = Query::from('test', $this->pdo)
        ->select()
        ->between('column_1', 1, 100)
    ;

    expect($select->__toString())->toEqual(
        'SELECT * FROM test WHERE (test.column_1 BETWEEN :b_start AND :b_end)'
    );

    expect($select->queryBind())->toEqual(
        'SELECT * FROM test WHERE (test.column_1 BETWEEN 1 AND 100)'
    );
});

test('it can select compare', function (): void {
    $select = Query::from('test', $this->pdo)
        ->select()
        ->compare('column_1', '=', 100)
    ;

    expect($select->__toString())->toEqual(
        'SELECT * FROM test WHERE ( (test.column_1 = :column_1) )'
    );

    expect($select->queryBind())->toEqual(
        'SELECT * FROM test WHERE ( (test.column_1 = 100) )'
    );
});

test('it can select equal', function (): void {
    $select = Query::from('test', $this->pdo)
        ->select()
        ->equal('column_1', 100)
    ;

    expect($select->__toString())->toEqual(
        'SELECT * FROM test WHERE ( (test.column_1 = :column_1) )'
    );

    expect($select->queryBind())->toEqual(
        'SELECT * FROM test WHERE ( (test.column_1 = 100) )'
    );
});

test('it can select in', function (): void {
    $select = Query::from('test', $this->pdo)
        ->select()
        ->in('column_1', [1, 2])
    ;

    expect($select->__toString())->toEqual(
        'SELECT * FROM test WHERE (test.column_1 IN (:in_0, :in_1))'
    );

    expect($select->queryBind())->toEqual(
        'SELECT * FROM test WHERE (test.column_1 IN (1, 2))'
    );
});

test('it can select like', function (): void {
    $select = Query::from('test', $this->pdo)
        ->select()
        ->like('column_1', 'test')
    ;

    expect($select->__toString())->toEqual(
        'SELECT * FROM test WHERE ( (test.column_1 LIKE :column_1) )'
    );

    expect($select->queryBind())->toEqual(
        "SELECT * FROM test WHERE ( (test.column_1 LIKE 'test') )"
    );
});

test('it can select where', function (): void {
    $select = Query::from('test', $this->pdo)
        ->select()
        ->where('a < :a OR b > :b', [[':a', 1], [':b', 2]])
    ;

    expect($select->__toString())->toEqual(
        'SELECT * FROM test WHERE a < :a OR b > :b'
    );

    expect($select->queryBind())->toEqual(
        'SELECT * FROM test WHERE a < 1 OR b > 2'
    );
});

test('it correct select multy column', function (): void {
    $select = Query::from('test', $this->pdo)
        ->select(['column_1', 'column_2', 'column_3'])
        ->equal('column_1', 123)
        ->equal('column_2', 'abc')
        ->equal('column_3', true);

    expect($select->__toString())->toEqual(
        'SELECT column_1, column_2, column_3 FROM test WHERE ( (test.column_1 = :column_1) '
        . 'AND (test.column_2 = :column_2) AND (test.column_3 = :column_3) )'
    );

    expect($select->queryBind())->toEqual(
        "SELECT column_1, column_2, column_3 FROM test WHERE ( (test.column_1 = 123) "
        . "AND (test.column_2 = 'abc') AND (test.column_3 = true) )"
    );
});

test('it correct select with strict off', function (): void {
    $select = Query::from('test', $this->pdo)
        ->select(['column_1', 'column_2', 'column_3'])
        ->equal('column_1', 123)
        ->equal('column_2', 'abc')
        ->strictMode(false);

    expect($select->__toString())->toEqual(
        'SELECT column_1, column_2, column_3 FROM test WHERE ( (test.column_1 = :column_1) '
        . 'OR (test.column_2 = :column_2) )'
    );

    expect($select->queryBind())->toEqual(
        "SELECT column_1, column_2, column_3 FROM test WHERE ( (test.column_1 = 123) OR (test.column_2 = 'abc') )"
    );
});

test('it can generate where exis query', function (): void {
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

    expect($select->__toString())->toEqual(
        'SELECT * FROM base_1 WHERE EXISTS ( SELECT * FROM base_2 WHERE ( (base_2.test = :test) ) '
        . 'AND base_1.id = base_2.id ) ORDER BY base_1.id ASC LIMIT 1, 10'
    );

    expect($select->queryBind())->toEqual(
        "SELECT * FROM base_1 WHERE EXISTS ( SELECT * FROM base_2 WHERE ( (base_2.test = 'success') ) "
        . "AND base_1.id = base_2.id ) ORDER BY base_1.id ASC LIMIT 1, 10"
    );
});

test('it can generate where not exis query', function (): void {
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

    expect($select->__toString())->toEqual(
        'SELECT * FROM base_1 WHERE NOT EXISTS ( SELECT * FROM base_2 WHERE ( (base_2.test = :test) ) '
        . 'AND base_1.id = base_2.id ) ORDER BY base_1.id ASC LIMIT 1, 10'
    );

    expect($select->queryBind())->toEqual(
        "SELECT * FROM base_1 WHERE NOT EXISTS ( SELECT * FROM base_2 WHERE ( (base_2.test = 'success') ) "
        . "AND base_1.id = base_2.id ) ORDER BY base_1.id ASC LIMIT 1, 10"
    );
});

test('it can generate select with where query', function (): void {
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

    expect($select->__toString())->toEqual(
        'SELECT * FROM base_1 WHERE user = ( SELECT * FROM base_2 WHERE ( (base_2.test = :test) ) '
        . 'AND base_1.id = base_2.id ) ORDER BY base_1.id ASC LIMIT 1, 10'
    );

    expect($select->queryBind())->toEqual(
        "SELECT * FROM base_1 WHERE user = ( SELECT * FROM base_2 WHERE ( (base_2.test = 'success') ) "
        . "AND base_1.id = base_2.id ) ORDER BY base_1.id ASC LIMIT 1, 10"
    );
});

test('it can generate select with sub query', function (): void {
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

    expect($select->__toString())->toEqual(
        'SELECT user.id as id FROM (SELECT id FROM base_2 WHERE (base_2.test IN (:in_0))) '
        . 'AS user ORDER BY user.id ASC LIMIT 1, 10'
    );

    expect($select->queryBind())->toEqual(
        "SELECT user.id as id FROM (SELECT id FROM base_2 WHERE (base_2.test IN ('success'))) "
        . "AS user ORDER BY user.id ASC LIMIT 1, 10"
    );
});

test('it can select with group by', function (): void {
    $select = Query::from('test', $this->pdo)
        ->select()
        ->groupBy('culumn_1')
    ;
    $select_multy = Query::from('test', $this->pdo)
        ->select()
        ->groupBy('culumn_1', 'column_2')
    ;

    expect($select->__toString())->toEqual(
        'SELECT * FROM test GROUP BY culumn_1'
    );

    expect($select_multy->__toString())->toEqual(
        'SELECT * FROM test GROUP BY culumn_1, column_2'
    );
});

test('it can generate multy order', function (): void {
    $select = Query::from('base_1', $this->pdo)
        ->select()
        ->order('id', Query::ORDER_ASC)
        ->order('name', Query::ORDER_DESC)
    ;

    expect($select->__toString())->toEqual(
        'SELECT * FROM base_1 ORDER BY base_1.id ASC, base_1.name DESC'
    );
});

test('it can select with order if not null', function (): void {
    $select = Query::from('test', $this->pdo)
        ->select()
        ->orderIfNotNull('column_1');

    expect($select->__toString())->toEqual(
        'SELECT * FROM test ORDER BY test.column_1 IS NOT NULL ASC'
    );
});

test('it can select with order if null', function (): void {
    $select = Query::from('test', $this->pdo)
        ->select()
        ->orderIfNull('column_1');

    expect($select->__toString())->toEqual(
        'SELECT * FROM test ORDER BY test.column_1 IS NULL ASC'
    );
});