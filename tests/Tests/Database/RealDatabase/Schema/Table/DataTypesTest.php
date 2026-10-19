<?php

declare(strict_types=1);

namespace Tests\Database\RealDatabase\Schema\Table;

use Omega\Database\Schema\Table\Create;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Database\ManagesDatabase;
use Tests\TestCase;

#[CoversClass(Create::class)]
final class DataTypesTest extends TestCase
{
    use ManagesDatabase;

    protected function tearDown(): void
    {
        $this->dropConnection();

        parent::tearDown();
    }

    #[DataProvider('engineProvider')]
    public function testItCanExecuteQueryNumericDataTypes(string $engine): void
    {
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

        $this->assertTrue($schema->execute());
    }

    #[DataProvider('engineProvider')]
    public function testItCanExecuteQueryStringDataTypes(string $engine): void
    {
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

        $this->assertTrue($schema->execute());
    }

    #[DataProvider('engineProvider')]
    public function testItCanExecuteQueryDateTimeDataTypes(string $engine): void
    {
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

        $this->assertTrue($schema->execute());
    }
}
