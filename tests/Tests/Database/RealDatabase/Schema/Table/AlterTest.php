<?php

declare(strict_types=1);

namespace Tests\Database\RealDatabase\Schema\Table;

use Omega\Database\Schema\Table\Alter;
use PHPUnit\Framework\Attributes\CoversClass;
use Tests\Database\AbstractTestDatabase;

#[CoversClass((Alter::class))]
final class AlterTest extends AbstractTestDatabase
{
    protected function setUp(): void
    {
        $this->createConnection();
        $this->createUserSchema();
        $this->pdo
            ->query('CREATE TABLE profiles (
                user varchar(10) NOT NULL,
                name varchar(500) NOT NULL,
                stat int(2) NOT NULL,
                create_at int(12) NOT NULL,
                update_at int(12) NOT NULL,
                PRIMARY KEY (user)
              )')
            ->execute();
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
    public function testItCanExcuteQueryUsingModifyColumn()
    {
        $schema = new Alter(
            $this->env['database'],
            'profiles',
            $this->pdoSchema
        );
        $schema->column('user')->varchar(15);

        $this->assertTrue($schema->execute());
    }

    /**
     * @test
     *
     * @group database
     */
    public function testItCanExcuteQueryUsingAddColumn()
    {
        $schema = new Alter(
            $this->env['database'],
            'profiles',
            $this->pdoSchema
        );
        $schema->add('PersonID')->int();
        $schema->add('LastName')->varchar(255);

        $this->assertTrue($schema->execute());
    }

    /**
     * @test
     *
     * @group database
     */
    public function testItCanExcuteQueryUsingDropColumn()
    {
        $schema = new Alter(
            $this->env['database'],
            'profiles',
            $this->pdoSchema
        );
        $schema->drop('create_at');
        $schema->drop('update_at');

        $this->assertTrue($schema->execute());
    }

    /**
     * @test
     *
     * @group database
     * @group not-for-mysql5.7
     */
    public function testItCanExcuteQueryUsingRenameColumn()
    {
        $schema = new Alter(
            $this->env['database'],
            'profiles',
            $this->pdoSchema
        );
        $schema->rename('stat', 'take');

        $this->assertTrue($schema->execute());
    }

    /**
     * @test
     *
     * @group database
     * @group not-for-mysql5.7
     */
    public function testItCanExcuteQueryUsingRenamesColumn()
    {
        $schema = new Alter(
            $this->env['database'],
            'profiles',
            $this->pdoSchema
        );
        $schema->rename('stat', 'take');
        $schema->rename('update_at', 'modify_at');

        $this->assertTrue($schema->execute());
    }

    /**
     * @test
     *
     * @group database
     * @group not-for-mysql5.7
     */
    public function testItCanExcuteQueryUsingAlterColumn()
    {
        $schema = new Alter(
            $this->env['database'],
            'profiles',
            $this->pdoSchema
        );
        $schema->column('user')->varchar(15);
        $schema->add('PersonID')->int();
        $schema->drop('create_at');
        $schema->rename('stat', 'take');

        $this->assertTrue($schema->execute());
    }

    /**
     * @test
     *
     * @group database
     */
    public function testItCanExcuteQueryUsingModifyAddWithOrder()
    {
        $schema = new Alter(
            $this->env['database'],
            'profiles',
            $this->pdoSchema
        );
        $schema->add('uuid')->varchar(15)->first();
        $schema->add('last_name')->varchar(32)->after('name');

        $this->assertTrue($schema->execute());
    }

    /**
     * @test
     *
     * @group database
     */
    public function testItCanExcuteQueryUsingModifyColumnWithOrder()
    {
        $schema = new Alter(
            $this->env['database'],
            'profiles',
            $this->pdoSchema
        );
        $schema('create_at')->varchar(15)->after('user');
        $schema->column('update_at')->varchar(15)->after('user');

        $this->assertTrue($schema->execute());
    }
}
