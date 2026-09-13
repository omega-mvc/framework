<?php

declare(strict_types=1);

namespace Tests\Database\RealDatabase\Schema\Table;

use Omega\Database\Schema\Table\Truncate;
use Tests\Database\Asserts\UserTrait;
use Tests\Database\ManagesDatabase;

uses(ManagesDatabase::class);
uses(UserTrait::class);

covers(Truncate::class);

afterEach(function (): void {
    $this->dropConnection();
});

test('it can generate truncate database', function (string $engine): void {
    $this->requiresOneOf($engine, ['mysql', 'mariadb'], 'TRUNCATE TABLE statement');

    $this->createConnection($engine);
    $this->seedDefaultUser();

    $schema = new Truncate($this->pdoSchema->getDatabase(), 'users', $this->pdoSchema);

    expect($schema->execute())->toBeTrue();
})->with(ManagesDatabase::engineProvider());