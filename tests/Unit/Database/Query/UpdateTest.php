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

test('it can update between', function (): void {
    $update = Query::from('test', $this->pdo)
        ->update()
        ->value('a', 'b')
        ->between('column_1', 1, 100)
    ;

    expect($update->__toString())->toEqual(
        'UPDATE test SET a = :bind_a WHERE (test.column_1 BETWEEN :b_start AND :b_end)'
    );

    expect($update->queryBind())->toEqual(
        "UPDATE test SET a = 'b' WHERE (test.column_1 BETWEEN 1 AND 100)"
    );
});

test('it can update compare', function (): void {
    $update = Query::from('test', $this->pdo)
        ->update()
        ->value('a', 'b')
        ->compare('column_1', '=', 100)
    ;

    expect($update->__toString())->toEqual(
        'UPDATE test SET a = :bind_a WHERE ( (test.column_1 = :column_1) )'
    );

    expect($update->queryBind())->toEqual(
        "UPDATE test SET a = 'b' WHERE ( (test.column_1 = 100) )"
    );
});

test('it can update equal', function (): void {
    $update = Query::from('test', $this->pdo)
        ->update()
        ->value('a', 'b')
        ->equal('column_1', 100)
    ;

    expect($update->__toString())->toEqual(
        'UPDATE test SET a = :bind_a WHERE ( (test.column_1 = :column_1) )'
    );

    expect($update->queryBind())->toEqual(
        "UPDATE test SET a = 'b' WHERE ( (test.column_1 = 100) )"
    );
});

test('it can update in', function (): void {
    $update = Query::from('test', $this->pdo)
        ->update()
        ->value('a', 'b')
        ->in('column_1', [1, 2])
    ;

    expect($update->__toString())->toEqual(
        'UPDATE test SET a = :bind_a WHERE (test.column_1 IN (:in_0, :in_1))'
    );

    expect($update->queryBind())->toEqual(
        "UPDATE test SET a = 'b' WHERE (test.column_1 IN (1, 2))"
    );
});

test('it can update like', function (): void {
    $update = Query::from('test', $this->pdo)
        ->update()
        ->value('a', 'b')
        ->like('column_1', 'test')
    ;

    expect($update->__toString())->toEqual(
        'UPDATE test SET a = :bind_a WHERE ( (test.column_1 LIKE :column_1) )'
    );

    expect($update->queryBind())->toEqual(
        "UPDATE test SET a = 'b' WHERE ( (test.column_1 LIKE 'test') )"
    );
});

test('it can update where', function (): void {
    $update = Query::from('test', $this->pdo)
        ->update()
        ->value('a', 'b')
        ->where('a < :a OR b > :b', [[':a', 1], [':b', 2]])
    ;

    expect($update->__toString())->toEqual(
        'UPDATE test SET a = :bind_a WHERE a < :a OR b > :b'
    );

    expect($update->queryBind())->toEqual(
        "UPDATE test SET a = 'b' WHERE a < 1 OR b > 2"
    );
});

test('it correct update with strict off', function (): void {
    $update = Query::from('test', $this->pdo)
        ->update()
        ->value('a', 'b')
        ->equal('column_1', 123)
        ->equal('column_2', 'abc')
        ->strictMode(false);

    expect($update->__toString())->toEqual(
        'UPDATE test SET a = :bind_a WHERE ( (test.column_1 = :column_1) OR (test.column_2 = :column_2) )'
    );

    expect($update->queryBind())->toEqual(
        "UPDATE test SET a = 'b' WHERE ( (test.column_1 = 123) OR (test.column_2 = 'abc') )"
    );
});