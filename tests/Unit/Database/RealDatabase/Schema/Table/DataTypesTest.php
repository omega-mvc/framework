<?php

declare(strict_types=1);

namespace Tests\Database\RealDatabase\Schema\Table;

use Omega\Database\Schema\Table\Create;
use Tests\Database\ManagesDatabase;

uses(ManagesDatabase::class);

covers(Create::class);

afterEach(function (): void {
    $this->dropConnection();
});

test('it can execute query numeric data types', function (string $engine): void {
    $this->requiresOneOf($engine, ['mysql', 'mariadb'], 'MySQL data types');

    $this->createConnection($engine);

    $schema = new Create($this->pdoSchema->getDatabase(), 'profiles', $this->pdoSchema);

    $schema('col_int')->int();
    $schema('col_int_len')->int(11);
    $schema('col_tiny')->tinyint(1);
    $schema('col_small')->smallint();
    $schema('col_big')->bigint(20);
    $schema('col_float')->float();
    $schema('col_dec')->decimal(8, 2);
    $schema('col_double')->double(10, 3);
    $schema('col_bool')->boolean();

    expect($schema->execute())->toBeTrue();
})->with(ManagesDatabase::engineProvider());

test('it can execute query string data types', function (string $engine): void {
    $this->requiresOneOf($engine, ['mysql', 'mariadb'], 'MySQL data types');

    $this->createConnection($engine);

    $schema = new Create($this->pdoSchema->getDatabase(), 'profiles', $this->pdoSchema);

    $schema('col_char')->char();
    $schema('col_char_len')->char(10);
    $schema('col_varchar')->varchar(100);
    $schema('col_text')->text();
    $schema('col_blob')->blob();
    $schema('col_json')->json();
    $schema('col_enum')->enum(['a', 'b', 'c']);

    expect($schema->execute())->toBeTrue();
})->with(ManagesDatabase::engineProvider());

test('it can execute query date time data types', function (string $engine): void {
    $this->requiresOneOf($engine, ['mysql', 'mariadb'], 'MySQL data types');

    $this->createConnection($engine);

    $schema = new Create($this->pdoSchema->getDatabase(), 'profiles', $this->pdoSchema);

    $schema('col_time')->time();
    $schema('col_time_len')->time(4);
    $schema('col_timestamp')->timestamp()->default('CURRENT_TIMESTAMP', false);
    $schema('col_timestamp_len')->timestamp(6)->default('CURRENT_TIMESTAMP(6)', false);
    $schema('col_date')->date();
    $schema('col_datetime')->datetime();
    $schema('col_year')->year();

    expect($schema->execute())->toBeTrue();
})->with(ManagesDatabase::engineProvider());