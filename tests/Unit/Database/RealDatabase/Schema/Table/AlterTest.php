<?php

declare(strict_types=1);

namespace Tests\Database\RealDatabase\Schema\Table;

use Omega\Database\Schema\Table\Alter;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Database\ManagesDatabase;
use Tests\TestCase;

#[CoversClass(Alter::class)]
final class AlterTest extends TestCase
{
    use ManagesDatabase;

    protected function tearDown(): void
    {
        $this->dropConnection();

        parent::tearDown();
    }

    #[DataProvider('engineProvider')]
    public function testItCanExcuteQueryUsingModifyColumn(string $engine): void
    {
        $this->requiresOneOf($engine, ['mysql', 'mariadb'], 'MySQL ALTER TABLE syntax');

        $this->createConnection($engine);
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
            ->execute()
        ;

        $schema = new Alter(
            $this->pdoSchema->getDatabase(),
            'profiles',
            $this->pdoSchema
        );
        $schema->column('user')->varchar(15);

        $this->assertTrue($schema->execute());
    }

    #[DataProvider('engineProvider')]
    public function testItCanExcuteQueryUsingAddColumn(string $engine): void
    {
        $this->requiresOneOf($engine, ['mysql', 'mariadb'], 'MySQL ALTER TABLE syntax');

        $this->createConnection($engine);
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
            ->execute()
        ;

        $schema = new Alter(
            $this->pdoSchema->getDatabase(),
            'profiles',
            $this->pdoSchema
        );
        $schema->add('PersonID')->int();
        $schema->add('LastName')->varchar(255);

        $this->assertTrue($schema->execute());
    }

    #[DataProvider('engineProvider')]
    public function testItCanExcuteQueryUsingDropColumn(string $engine): void
    {
        $this->requiresOneOf($engine, ['mysql', 'mariadb'], 'MySQL ALTER TABLE syntax');

        $this->createConnection($engine);
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
            ->execute()
        ;

        $schema = new Alter(
            $this->pdoSchema->getDatabase(),
            'profiles',
            $this->pdoSchema
        );
        $schema->drop('create_at');
        $schema->drop('update_at');

        $this->assertTrue($schema->execute());
    }

    #[DataProvider('engineProvider')]
    public function testItCanExcuteQueryUsingRenameColumn(string $engine): void
    {
        $this->requiresOneOf($engine, ['mysql', 'mariadb'], 'MySQL ALTER TABLE syntax');

        $this->createConnection($engine);
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
            ->execute()
        ;

        $schema = new Alter(
            $this->pdoSchema->getDatabase(),
            'profiles',
            $this->pdoSchema
        );
        $schema->rename('stat', 'take');

        $this->assertTrue($schema->execute());
    }

    #[DataProvider('engineProvider')]
    public function testItCanExcuteQueryUsingRenamesColumn(string $engine): void
    {
        $this->requiresOneOf($engine, ['mysql', 'mariadb'], 'MySQL ALTER TABLE syntax');

        $this->createConnection($engine);
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
            ->execute()
        ;

        $schema = new Alter(
            $this->pdoSchema->getDatabase(),
            'profiles',
            $this->pdoSchema
        );
        $schema->rename('stat', 'take');
        $schema->rename('update_at', 'modify_at');

        $this->assertTrue($schema->execute());
    }

    #[DataProvider('engineProvider')]
    public function testItCanExcuteQueryUsingAlterColumn(string $engine): void
    {
        $this->requiresOneOf($engine, ['mysql', 'mariadb'], 'MySQL ALTER TABLE syntax');

        $this->createConnection($engine);
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
            ->execute()
        ;

        $schema = new Alter(
            $this->pdoSchema->getDatabase(),
            'profiles',
            $this->pdoSchema
        );
        $schema->column('user')->varchar(15);
        $schema->add('PersonID')->int();
        $schema->drop('create_at');
        $schema->rename('stat', 'take');

        $this->assertTrue($schema->execute());
    }

    #[DataProvider('engineProvider')]
    public function testItCanExcuteQueryUsingModifyAddWithOrder(string $engine): void
    {
        $this->requiresOneOf($engine, ['mysql', 'mariadb'], 'MySQL ALTER TABLE syntax');

        $this->createConnection($engine);
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
            ->execute()
        ;

        $schema = new Alter(
            $this->pdoSchema->getDatabase(),
            'profiles',
            $this->pdoSchema
        );
        $schema->add('uuid')->varchar(15)->first();
        $schema->add('last_name')->varchar(32)->after('name');

        $this->assertTrue($schema->execute());
    }

    #[DataProvider('engineProvider')]
    public function testItCanExcuteQueryUsingModifyColumnWithOrder(string $engine): void
    {
        $this->requiresOneOf($engine, ['mysql', 'mariadb'], 'MySQL ALTER TABLE syntax');

        $this->createConnection($engine);
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
            ->execute()
        ;

        $schema = new Alter(
            $this->pdoSchema->getDatabase(),
            'profiles',
            $this->pdoSchema
        );
        $schema('create_at')->varchar(15)->after('user');
        $schema->column('update_at')->varchar(15)->after('user');

        $this->assertTrue($schema->execute());
    }
}
