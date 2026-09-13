<?php

declare(strict_types=1);

namespace Tests\Database\Query\Schema\Table;

use Omega\Database\ConnectionInterface;
use Omega\Database\Schema\SchemaConnection;
use Omega\Database\Schema\Table\Alter;

covers(Alter::class);

beforeEach(function (): void {
    $this->pdo       = $this->createStub(ConnectionInterface::class);
    $this->pdoSchema = $this->createStub(SchemaConnection::class);
});

test('it can generate query using modify column', function (): void {
    $schema = new Alter('testing_db', 'test', $this->pdoSchema);
    $schema->column('create_add')->int(17);
    $schema('update_add')->int(17);

    expect($schema->__toString())->toEqual(
        'ALTER TABLE testing_db.test MODIFY COLUMN create_add int(17), MODIFY COLUMN update_add int(17);'
    );
});

test('it can generate query using add column', function (): void {
    $schema = new Alter('testing_db', 'test', $this->pdoSchema);
    $schema->add('PersonID')->int();
    $schema->add('LastName')->varchar(255);

    expect($schema->__toString())->toEqual(
        'ALTER TABLE testing_db.test ADD PersonID int, ADD LastName varchar(255);'
    );
});

test('it can generate query using drop column', function (): void {
    $schema = new Alter('testing_db', 'test', $this->pdoSchema);
    $schema->drop('PersonID');
    $schema->drop('LastName');

    expect($schema->__toString())->toEqual(
        'ALTER TABLE testing_db.test DROP COLUMN PersonID, DROP COLUMN LastName;'
    );
});

test('it can generate query using rename column', function (): void {
    $schema = new Alter('testing_db', 'test', $this->pdoSchema);
    $schema->rename('PersonID', 'person_id');

    expect($schema->__toString())->toEqual(
        'ALTER TABLE testing_db.test RENAME COLUMN PersonID TO person_id;'
    );
});

test('it can generate query using rename column multyple', function (): void {
    $schema = new Alter('testing_db', 'test', $this->pdoSchema);
    $schema->rename('PersonID', 'person');
    $schema->rename('PersonID', 'person_id');

    expect($schema->__toString())->toEqual(
        'ALTER TABLE testing_db.test RENAME COLUMN PersonID TO person_id;'
    );
});

test('it can generate query using alters column', function (): void {
    $schema = new Alter('testing_db', 'test', $this->pdoSchema);
    $schema->add('PersonID')->int(4);
    $schema->drop('LastName');
    $schema->column('create_add')->int(17);

    expect($schema->__toString())->toEqual(
        'ALTER TABLE testing_db.test MODIFY COLUMN create_add int(17), ADD PersonID int(4), DROP COLUMN LastName;'
    );
});

test('it can generate query using modify column and orderit', function (): void {
    $schema = new Alter('testing_db', 'test', $this->pdoSchema);
    $schema->column('uuid')->int(17)->first();
    $schema->column('create_add')->after('id');

    expect($schema->__toString())->toEqual(
        'ALTER TABLE testing_db.test MODIFY COLUMN uuid int(17) FIRST, MODIFY COLUMN create_add AFTER id;'
    );
});

test('it can generate query using add column and orderit', function (): void {
    $schema = new Alter('testing_db', 'test', $this->pdoSchema);
    $schema->add('uuid')->int(17)->first();
    $schema->add('create_add')->int(17)->after('id');

    expect($schema->__toString())->toEqual(
        'ALTER TABLE testing_db.test ADD uuid int(17) FIRST, ADD create_add int(17) AFTER id;'
    );
});