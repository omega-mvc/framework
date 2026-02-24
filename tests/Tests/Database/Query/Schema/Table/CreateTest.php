<?php

declare(strict_types=1);

namespace Tests\Database\Query\Schema\Table;

use Omega\Database\Schema\Table\Column;
use Omega\Database\Schema\Table\Create;
use PHPUnit\Framework\Attributes\CoversClass;
use Tests\Database\TestDatabaseQuery;

#[CoversClass(Column::class)]
#[CoversClass(Create::class)]
final class CreateTest extends TestDatabaseQuery
{
    /** @test */
    public function testItCanGenerateQueryUsingAddColumn()
    {
        $schema = new Create('testing_db', 'test', $this->pdoSchema);
        $schema->addColumn()->raw('PersonID int');
        $schema->addColumn()->raw('LastName varchar(255)');
        $schema->primaryKey('PersonID');

        $this->assertEquals(
            'CREATE TABLE testing_db.test ( PersonID int, LastName varchar(255), PRIMARY KEY (PersonID) )',
            $schema->__toString()
        );
    }

    /** @test */
    public function testItCanGenerateQueryUsingWithMultyPrimeryKey()
    {
        $schema = new Create('testing_db', 'test', $this->pdoSchema);
        $schema->addColumn()->raw('PersonID int');
        $schema->addColumn()->raw('LastName varchar(255)');
        $schema->primaryKey('PersonID');
        $schema->primaryKey('LastName');

        $this->assertEquals(
            'CREATE TABLE testing_db.test ( PersonID int, LastName varchar(255), PRIMARY KEY (PersonID, LastName) )',
            $schema->__toString()
        );
    }

    /** @test */
    public function testItCanGenerateQueryUsingAddColumnWithoutPrimeryKey()
    {
        $schema = new Create('testing_db', 'test', $this->pdoSchema);
        $schema->addColumn()->raw('PersonID int');
        $schema->addColumn()->raw('LastName varchar(255)');

        $this->assertEquals(
            'CREATE TABLE testing_db.test ( PersonID int, LastName varchar(255) )',
            $schema->__toString()
        );
    }

    /** @test */
    public function testItCanGenerateQueryUsingAddColumnWithUnique()
    {
        $schema = new Create('testing_db', 'test', $this->pdoSchema);
        $schema->addColumn()->raw('PersonID int');
        $schema->addColumn()->raw('LastName varchar(255)');
        $schema->unique('PersonID');

        $this->assertEquals(
            'CREATE TABLE testing_db.test ( PersonID int, LastName varchar(255), UNIQUE (PersonID) )',
            $schema->__toString()
        );
    }

    /** @test */
    public function testItCanGenerateQueryUsingAddColumnWithMultyUnique()
    {
        $schema = new Create('testing_db', 'test', $this->pdoSchema);
        $schema->addColumn()->raw('PersonID int');
        $schema->addColumn()->raw('LastName varchar(255)');
        $schema->unique('PersonID');
        $schema->unique('LastName');

        $this->assertEquals(
            'CREATE TABLE testing_db.test ( PersonID int, LastName varchar(255), UNIQUE (PersonID, LastName) )',
            $schema->__toString()
        );
    }

    /** @test */
    public function testItCanGenerateQueryUsingColumns()
    {
        $schema = new Create('testing_db', 'test', $this->pdoSchema);
        $schema->columns([
            new Column()->raw('PersonID int'),
            new Column()->raw('LastName varchar(255)'),
        ]);
        $schema->primaryKey('PersonID');

        $this->assertEquals(
            'CREATE TABLE testing_db.test ( PersonID int, LastName varchar(255), PRIMARY KEY (PersonID) )',
            $schema->__toString()
        );
    }

    /** @test */
    public function testItCanGenerateQuery()
    {
        $schema = new Create('testing_db', 'test', $this->pdoSchema);
        $schema('PersonID')->int();
        $schema('LastName')->varchar(255);
        $schema->primaryKey('PersonID');

        $this->assertEquals(
            'CREATE TABLE testing_db.test ( PersonID int, LastName varchar(255), PRIMARY KEY (PersonID) )',
            $schema->__toString()
        );
    }

    /** @test */
    public function testItCanGenerateDefaultConstraint()
    {
        $schema = new Create('testing_db', 'test', $this->pdoSchema);
        $schema('PersonID')->int()->unsigned()->default(1);
        $schema('LastName')->varchar(255)->default('-');
        $schema('sufix')->varchar(15)->defaultNull();
        $schema->primaryKey('PersonID');

        $this->assertEquals(
            "CREATE TABLE testing_db.test ( PersonID int UNSIGNED DEFAULT 1, LastName varchar(255) DEFAULT '-', sufix varchar(15) DEFAULT NULL, PRIMARY KEY (PersonID) )",
            $schema->__toString()
        );
    }

    /** @test */
    public function testItCanGenerateQueryWithDatatypeAndConstrait()
    {
        $schema = new Create('testing_db', 'test', $this->pdoSchema);
        $schema('PersonID')->int()->notNull();
        $schema('LastName')->varchar(255)->null();
        $schema->primaryKey('PersonID');

        $this->assertEquals(
            'CREATE TABLE testing_db.test ( PersonID int NOT NULL, LastName varchar(255) NULL, PRIMARY KEY (PersonID) )',
            $schema->__toString()
        );
    }

    /** @test */
    public function testItCanGenerateQueryWithStorageEngine()
    {
        $schema = new Create('testing_db', 'test', $this->pdoSchema);
        $schema->addColumn()->raw('PersonID int');
        $schema->addColumn()->raw('LastName varchar(255)');
        $schema->primaryKey('PersonID');
        $schema->engine(Create::INNODB);

        $this->assertEquals(
            'CREATE TABLE testing_db.test ( PersonID int, LastName varchar(255), PRIMARY KEY (PersonID) ) ENGINE=INNODB',
            $schema->__toString()
        );
    }

    /** @test */
    public function testItCanGenerateQueryWithCharacterSet()
    {
        $schema = new Create('testing_db', 'test', $this->pdoSchema);
        $schema->addColumn()->raw('PersonID int');
        $schema->addColumn()->raw('LastName varchar(255)');
        $schema->primaryKey('PersonID');
        $schema->character('utf8mb4');

        $this->assertEquals(
            'CREATE TABLE testing_db.test ( PersonID int, LastName varchar(255), PRIMARY KEY (PersonID) ) CHARACTER SET utf8mb4',
            $schema->__toString()
        );
    }

    /** @test */
    public function testItCanGenerateQueryWithEngineStoreAndCharacterSet()
    {
        $schema = new Create('testing_db', 'test', $this->pdoSchema);
        $schema->addColumn()->raw('PersonID int');
        $schema->addColumn()->raw('LastName varchar(255)');
        $schema->primaryKey('PersonID');
        $schema->engine(Create::INNODB);
        $schema->character('utf8mb4');

        $this->assertEquals(
            'CREATE TABLE testing_db.test ( PersonID int, LastName varchar(255), PRIMARY KEY (PersonID) ) ENGINE=INNODB CHARACTER SET utf8mb4',
            $schema->__toString()
        );
    }

    /** @test */
    public function testItCanGenerateQueryWithComment(): void
    {
        $schema = new Create('testing_db', 'test', $this->pdoSchema);
        $schema('PersonID')->int();
        $schema('LastName')->varchar(255)->comment('The last name of the person associated with this ID');
        $schema->primaryKey('PersonID');

        $this->assertEquals(
            'CREATE TABLE testing_db.test ( PersonID int, LastName varchar(255) COMMENT \'The last name of the person associated with this ID\', PRIMARY KEY (PersonID) )',
            $schema->__toString()
        );
    }
}
