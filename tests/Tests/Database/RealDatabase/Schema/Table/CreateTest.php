<?php

declare(strict_types=1);

namespace Tests\Database\RealDatabase\Schema\Table;

use Omega\Database\Schema\Table\Create;
use Tests\Database\AbstractTestDatabase;

final class CreateTest extends AbstractTestDatabase
{
    protected function setUp(): void
    {
        $this->createConnection();
    }

    protected function tearDown(): void
    {
        $this->dropConnection();
    }

    /**
     * @test
     *
     * @group database
     */
    public function testItCanGenerateCreateDatabase()
    {
        $schema = new Create($this->env['database'], 'profiles', $this->pdo_schema);

        $schema('id')->int(3)->notNull();
        $schema('name')->varchar(32)->notNull();
        $schema('gender')->int(1);
        $schema->primaryKey('id');

        $this->assertTrue($schema->execute());
    }

    /**
     * @test
     *
     * @group database
     */
    public function testItCanExecuteQueryWithMultyPrimeryKey()
    {
        $schema = new Create($this->env['database'], 'profiles', $this->pdo_schema);

        $schema('id')->int(3)->notNull();
        $schema('xid')->int(3)->notNull();
        $schema('name')->varchar(32)->notNull();
        $schema('gender')->int(1);
        $schema->primaryKey('id');
        $schema->primaryKey('xid');

        $this->assertTrue($schema->execute());
    }

    /**
     * @test
     *
     * @group database
     */
    public function testItCanExecuteQueryWithMultyUniqe()
    {
        $schema = new Create($this->env['database'], 'profiles', $this->pdo_schema);

        $schema('id')->int(3)->notNull();
        $schema('name')->varchar(32)->notNull();
        $schema('gender')->int(1);
        $schema->unique('id');
        $schema->unique('name');

        $this->assertTrue($schema->execute());
    }

    /**
     * @test
     *
     * @group database
     */
    public function testItCanGenerateCreateDatabaseWithEngine()
    {
        $schema = new Create($this->env['database'], 'profiles', $this->pdo_schema);

        $schema('id')->int(3)->notNull();
        $schema('name')->varchar(32)->notNull();
        $schema('gender')->int(1);
        $schema->primaryKey('id');
        $schema->engine(Create::INNODB);
        $schema->character('utf8mb4');

        $this->assertTrue($schema->execute());
    }

    /**
     * @test
     *
     * @group database
     */
    public function testItCanGenerateDefaultConstraint()
    {
        $schema = new Create($this->env['database'], 'profiles', $this->pdo_schema);
        $schema('PersonID')->int()->unsigned()->default(1);
        $schema('LastName')->varchar(255)->default('-');
        $schema('sufix')->varchar(15)->defaultNull();
        $schema->primaryKey('PersonID');

        $this->assertTrue($schema->execute());
    }

    /**
     * @test
     *
     * @group database
     */
    public function testItCanGenerateQueryWithComment(): void
    {
        $schema = new Create('testing_db', 'test', $this->pdo_schema);
        $schema('PersonID')->int();
        $schema('LastName')->varchar(255)->comment('The last name of the person associated with this ID');
        $schema->primaryKey('PersonID');

        $this->assertTrue($schema->execute());
    }
}
