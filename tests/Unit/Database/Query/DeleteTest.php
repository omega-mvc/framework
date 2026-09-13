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

test('it can delete between', function (): void {
    $delete = Query::from('test', $this->pdo)
        ->delete()
        ->between('column_1', 1, 100)
    ;

    expect($delete->__toString())->toEqual(
        'DELETE FROM test WHERE (test.column_1 BETWEEN :b_start AND :b_end)'
    );

    expect($delete->queryBind())->toEqual(
        'DELETE FROM test WHERE (test.column_1 BETWEEN 1 AND 100)'
    );
});

test('it can delete compare', function (): void {
    $delete = Query::from('test', $this->pdo)
        ->delete()
        ->compare('column_1', '=', 100)
    ;

    expect($delete->__toString())->toEqual(
        'DELETE FROM test WHERE ( (test.column_1 = :column_1) )'
    );

    expect($delete->queryBind())->toEqual(
        'DELETE FROM test WHERE ( (test.column_1 = 100) )'
    );
});

test('it can delete equal', function (): void {
    $delete = Query::from('test', $this->pdo)
        ->delete()
        ->equal('column_1', 100)
    ;

    expect($delete->__toString())->toEqual(
        'DELETE FROM test WHERE ( (test.column_1 = :column_1) )'
    );

    expect($delete->queryBind())->toEqual(
        'DELETE FROM test WHERE ( (test.column_1 = 100) )'
    );
});

test('it can delete in', function (): void {
    $delete = Query::from('test', $this->pdo)
        ->delete()
        ->in('column_1', [1, 2])
    ;

    expect($delete->__toString())->toEqual(
        'DELETE FROM test WHERE (test.column_1 IN (:in_0, :in_1))'
    );

    expect($delete->queryBind())->toEqual(
        'DELETE FROM test WHERE (test.column_1 IN (1, 2))'
    );
});

test('it can delete like', function (): void {
    $delete = Query::from('test', $this->pdo)
        ->delete()
        ->like('column_1', 'test')
    ;

    expect($delete->__toString())->toEqual(
        'DELETE FROM test WHERE ( (test.column_1 LIKE :column_1) )'
    );

    expect($delete->queryBind())->toEqual(
        "DELETE FROM test WHERE ( (test.column_1 LIKE 'test') )"
    );
});

test('it can delete where', function (): void {
    $delete = Query::from('test', $this->pdo)
        ->delete()
        ->where('a < :a OR b > :b', [[':a', 1], [':b', 2]])
    ;

    expect($delete->__toString())->toEqual(
        'DELETE FROM test WHERE a < :a OR b > :b'
    );

    expect($delete->queryBind())->toEqual(
        'DELETE FROM test WHERE a < 1 OR b > 2'
    );
});

test('it correct delete with strict off', function (): void {
    $delete = Query::from('test', $this->pdo)
        ->delete()
        ->equal('column_1', 123)
        ->equal('column_2', 'abc')
        ->strictMode(false);

    expect($delete->__toString())->toEqual(
        'DELETE FROM test WHERE ( (test.column_1 = :column_1) OR (test.column_2 = :column_2) )'
    );

    expect($delete->queryBind())->toEqual(
        "DELETE FROM test WHERE ( (test.column_1 = 123) OR (test.column_2 = 'abc') )"
    );
});