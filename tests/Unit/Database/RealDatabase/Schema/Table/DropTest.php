<?php

declare(strict_types=1);

namespace Tests\Database\RealDatabase\Schema\Table;

use Omega\Database\Schema\Table\Drop;
use Tests\Database\Asserts\UserTrait;
use Tests\Database\ManagesDatabase;

uses(ManagesDatabase::class);
uses(UserTrait::class);

covers(Drop::class);

afterEach(function (): void {
    $this->dropConnection();
});

test('it can generate drop database', function (string $engine): void {
    $this->requiresOneOf($engine, ['mysql', 'mariadb'], 'database-qualified DROP TABLE');

    $this->createConnection($engine);
    $this->seedDefaultUser();

    $schema = new Drop($this->pdoSchema->getDatabase(), 'users', $this->pdoSchema);

    expect($schema->execute())->toBeTrue();
})->with(ManagesDatabase::engineProvider());