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

test('it correct insert', function (): void {
    $insert = Query::from('test', $this->pdo)
        ->insert()
        ->value('a', 1)
    ;

    expect($insert->__toString())->toEqual(
        'INSERT INTO test (a) VALUES (:bind_a)'
    );

    expect($insert->queryBind())->toEqual(
        'INSERT INTO test (a) VALUES (1)'
    );
});

test('it correct insert values', function (): void {
    $insert = Query::from('test', $this->pdo)
        ->insert()
        ->values([
            'a' => 'b',
            'c' => 'd',
            'e' => 'f',
        ])
    ;

    expect($insert->__toString())->toEqual(
        'INSERT INTO test (a, c, e) VALUES (:bind_a, :bind_c, :bind_e)'
    );

    expect($insert->queryBind())->toEqual(
        "INSERT INTO test (a, c, e) VALUES ('b', 'd', 'f')"
    );
});

test('it correct insert query multy values', function (): void {
    $insert = Query::from('test', $this->pdo)
        ->insert()
        ->values([
            'a' => 'b',
            'c' => 'd',
            'e' => 'f',
        ])
        ->value('g', 'h')
    ;

    expect($insert->__toString())->toEqual(
        'INSERT INTO test (a, c, e, g) VALUES (:bind_a, :bind_c, :bind_e, :bind_g)'
    );

    expect($insert->queryBind())->toEqual(
        "INSERT INTO test (a, c, e, g) VALUES ('b', 'd', 'f', 'h')"
    );
});

test('it correct insert query multy raws', function (): void {
    $insert = Query::from('test', $this->pdo)
        ->insert()
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

    expect($insert->__toString())->toEqual(
        'INSERT INTO test (a, c, e) VALUES (:bind_0_a, :bind_0_c, :bind_0_e), (:bind_1_a, :bind_1_c, :bind_1_e)'
    );

    expect($insert->queryBind())->toEqual(
        "INSERT INTO test (a, c, e) VALUES ('b', 'd', 'f'), ('b', 'd', 'f')"
    );
});

test('it correct insert on duplicate key update', function (): void {
    $insert = Query::from('test', $this->pdo)
        ->insert()
        ->value('a', 1)
        ->on('a')
    ;

    expect($insert->__toString())->toEqual(
        'INSERT INTO test (a) VALUES (:bind_a) ON DUPLICATE KEY UPDATE a = VALUES(a)'
    );

    expect($insert->queryBind())->toEqual(
        'INSERT INTO test (a) VALUES (1) ON DUPLICATE KEY UPDATE a = VALUES(a)'
    );
});