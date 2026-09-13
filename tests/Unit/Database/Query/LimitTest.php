<?php

declare(strict_types=1);

namespace Tests\Database\Query;

use Omega\Database\ConnectionInterface;
use Omega\Database\Query\Query;
use Omega\Database\Schema\SchemaConnection;

covers(Query::class);

beforeEach(function (): void {
    $this->pdo       = $this->createStub(ConnectionInterface::class);
    $this->pdoSchema = $this->createStub(SchemaConnection::class);
});

test('it correct select query with limit order', function (): void {
    $select = Query::from('test', $this->pdo)
        ->select()
        ->between('column_1', 1, 100)
        ->limit(1, 10)
        ->order('column_1', Query::ORDER_ASC);

    expect($select->__toString())->toEqual(
        'SELECT * FROM test WHERE (test.column_1 BETWEEN :b_start AND :b_end) '
        . 'ORDER BY test.column_1 ASC LIMIT 1, 10'
    );

    expect($select->queryBind())->toEqual(
        'SELECT * FROM test WHERE (test.column_1 BETWEEN 1 AND 100) ORDER BY test.column_1 ASC LIMIT 1, 10'
    );
});

test('it correct select query with limit end order with limit end less that zero', function (): void {
    $select = Query::from('test', $this->pdo)
        ->select()
        ->between('column_1', 1, 100)
        ->limit(2, -1)
        ->order('column_1', Query::ORDER_ASC);

    expect($select->__toString())->toEqual(
        'SELECT * FROM test WHERE (test.column_1 BETWEEN :b_start AND :b_end) '
        . 'ORDER BY test.column_1 ASC LIMIT 2, 0'
    );

    expect($select->queryBind())->toEqual(
        'SELECT * FROM test WHERE (test.column_1 BETWEEN 1 AND 100) ORDER BY test.column_1 ASC LIMIT 2, 0'
    );
});

test('it correct select query with limit start less that zero', function (): void {
    $select = Query::from('test', $this->pdo)
        ->select()
        ->between('column_1', 1, 100)
        ->limit(-1, 2)
        ->order('column_1', Query::ORDER_ASC);

    expect($select->__toString())->toEqual(
        'SELECT * FROM test WHERE (test.column_1 BETWEEN :b_start AND :b_end) ORDER BY test.column_1 ASC LIMIT 2'
    );

    expect($select->queryBind())->toEqual(
        'SELECT * FROM test WHERE (test.column_1 BETWEEN 1 AND 100) ORDER BY test.column_1 ASC LIMIT 2'
    );
});

test('it correct select query with limit and offet', function (): void {
    $select = Query::from('test', $this->pdo)
        ->select()
        ->between('column_1', 1, 100)
        ->limitStart(1)
        ->offset(10)
        ->order('column_1', Query::ORDER_ASC);

    expect($select->__toString())->toEqual(
        'SELECT * FROM test WHERE (test.column_1 BETWEEN :b_start AND :b_end) '
        . 'ORDER BY test.column_1 ASC LIMIT 1 OFFSET 10'
    );

    expect($select->queryBind())->toEqual(
        'SELECT * FROM test WHERE (test.column_1 BETWEEN 1 AND 100) ORDER BY test.column_1 ASC LIMIT 1 OFFSET 10'
    );
});

test('it correct select query with limit start and limit endt less that zero', function (): void {
    $select = Query::from('test', $this->pdo)
        ->select()
        ->between('column_1', 1, 100)
        ->limit(-1, -1)
        ->order('column_1', Query::ORDER_ASC);

    expect($select->__toString())->toEqual(
        'SELECT * FROM test WHERE (test.column_1 BETWEEN :b_start AND :b_end) ORDER BY test.column_1 ASC'
    );
    expect($select->__toString())->toEqual(
        'SELECT * FROM test WHERE (test.column_1 BETWEEN :b_start AND :b_end) ORDER BY test.column_1 ASC'
    );
});