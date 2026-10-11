<?php

declare(strict_types=1);

namespace Tests\Database\RealDatabase\Schema\Table;

use Omega\Database\Schema\Table\Create;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Database\ManagesDatabase;
use Tests\TestCase;

#[CoversClass(Create::class)]
final class CreateTest extends TestCase
{
    use ManagesDatabase;

    protected function tearDown(): void
    {
        $this->dropConnection();

        parent::tearDown();
    }

    #[DataProvider('engineProvider')]
    public function testItCanGenerateCreateDatabase(string $engine): void
    {
        $this->requiresOneOf($engine, ['mysql', 'mariadb'], 'MySQL CREATE TABLE clauses');

        $this->createConnection($engine);

        $schema = new Create($this->pdoSchema->getDatabase(), 'profiles', $this->pdoSchema);

        $schema('id')->int(3)->notNull();
        $schema('name')->varchar(32)->notNull();
        $schema('gender')->int(1);
        $schema->primaryKey('id');

        $this->assertTrue($schema->execute());
    }

    #[DataProvider('engineProvider')]
    public function testItCanExecuteQueryWithMultyPrimeryKey(string $engine): void
    {
        $this->requiresOneOf($engine, ['mysql', 'mariadb'], 'MySQL CREATE TABLE clauses');

        $this->createConnection($engine);

        $schema = new Create($this->pdoSchema->getDatabase(), 'profiles', $this->pdoSchema);

        $schema('id')->int(3)->notNull();
        $schema('xid')->int(3)->notNull();
        $schema('name')->varchar(32)->notNull();
        $schema('gender')->int(1);
        $schema->primaryKey('id');
        $schema->primaryKey('xid');

        $this->assertTrue($schema->execute());
    }

    #[DataProvider('engineProvider')]
    public function testItCanExecuteQueryWithMultyUniqe(string $engine): void
    {
        $this->requiresOneOf($engine, ['mysql', 'mariadb'], 'MySQL CREATE TABLE clauses');

        $this->createConnection($engine);

        $schema = new Create($this->pdoSchema->getDatabase(), 'profiles', $this->pdoSchema);

        $schema('id')->int(3)->notNull();
        $schema('name')->varchar(32)->notNull();
        $schema('gender')->int(1);
        $schema->unique('id');
        $schema->unique('name');

        $this->assertTrue($schema->execute());
    }

    #[DataProvider('engineProvider')]
    public function testItCanGenerateCreateDatabaseWithEngine(string $engine): void
    {
        $this->requiresOneOf($engine, ['mysql', 'mariadb'], 'MySQL ENGINE and CHARACTER SET clauses');

        $this->createConnection($engine);

        $schema = new Create($this->pdoSchema->getDatabase(), 'profiles', $this->pdoSchema);

        $schema('id')->int(3)->notNull();
        $schema('name')->varchar(32)->notNull();
        $schema('gender')->int(1);
        $schema->primaryKey('id');
        $schema->engine(Create::INNODB);
        $schema->character('utf8mb4');

        $this->assertTrue($schema->execute());
    }

    #[DataProvider('engineProvider')]
    public function testItCanGenerateDefaultConstraint(string $engine): void
    {
        $this->requiresOneOf($engine, ['mysql', 'mariadb'], 'MySQL CREATE TABLE clauses');

        $this->createConnection($engine);

        $schema = new Create($this->pdoSchema->getDatabase(), 'profiles', $this->pdoSchema);
        $schema('PersonID')->int()->unsigned()->default(1);
        $schema('LastName')->varchar(255)->default('-');
        $schema('sufix')->varchar(15)->defaultNull();
        $schema->primaryKey('PersonID');

        $this->assertTrue($schema->execute());
    }

    #[DataProvider('engineProvider')]
    public function testItCanGenerateQueryWithComment(string $engine): void
    {
        $this->requiresOneOf($engine, ['mysql', 'mariadb'], 'MySQL column COMMENT clause');

        $this->createConnection($engine);

        $schema = new Create($this->pdoSchema->getDatabase(), 'test', $this->pdoSchema);
        $schema('PersonID')->int();
        $schema('LastName')->varchar(255)->comment('The last name of the person associated with this ID');
        $schema->primaryKey('PersonID');

        $this->assertTrue($schema->execute());
    }
}
