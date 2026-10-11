<?php

declare(strict_types=1);

namespace Tests\Database\Model;

use Omega\Database\Query\Insert;
use Omega\Database\Query\Query;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Database\ManagesDatabase;
use Tests\Database\Support\Profile;
use Tests\TestCase;

use function array_values;

#[CoversClass(Query::class)]
#[CoversClass(Insert::class)]
final class CustomModelTest extends TestCase
{
    use ManagesDatabase;

    protected function tearDown(): void
    {
        $this->dropConnection();

        parent::tearDown();
    }

    #[DataProvider('engineProvider')]
    public function testItCanFilterModel(string $engine): void
    {
        $profiles = [
            'taylor' => [
                'user'   => 'taylor',
                'name'   => 'taylor otwell',
                'gender' => 'male',
                'age'    => 45,
            ],
            'nuno' => [
                'user'   => 'nuno',
                'name'   => 'nuno maduro',
                'gender' => 'male',
                'age'    => 40,
            ],
            'jesica' => [
                'user'   => 'jesica',
                'name'   => 'jesica w',
                'gender' => 'female',
                'age'    => 38,
            ],
            'pradana' => [
                'user'   => 'pradana',
                'name'   => 'sony pradana',
                'gender' => 'male',
                'age'    => 29,
            ],
        ];

        $this->createConnection($engine);
        $this->pdo->query('CREATE TABLE profiles (
        user      varchar(32)  NOT NULL,
        name      varchar(100) NOT NULL,
        gender    varchar(10) NOT NULL,
        age       int(3) NOT NULL,
        PRIMARY KEY (user)
    )')->execute();
        (new Insert('profiles', $this->pdo))
            ->rows(array_values($profiles))
            ->execute();

        $profiles = new Profile($this->pdo, []);
        $profiles->filterGender('male');
        $profiles->read();

        foreach ($profiles->get() as $profile) {
            $this->assertEquals('male', $profile->getter('gender'));
        }
    }

    #[DataProvider('engineProvider')]
    public function testItCanFilterModelChain(string $engine): void
    {
        $profiles = [
            'taylor' => [
                'user'   => 'taylor',
                'name'   => 'taylor otwell',
                'gender' => 'male',
                'age'    => 45,
            ],
            'nuno' => [
                'user'   => 'nuno',
                'name'   => 'nuno maduro',
                'gender' => 'male',
                'age'    => 40,
            ],
            'jesica' => [
                'user'   => 'jesica',
                'name'   => 'jesica w',
                'gender' => 'female',
                'age'    => 38,
            ],
            'pradana' => [
                'user'   => 'pradana',
                'name'   => 'sony pradana',
                'gender' => 'male',
                'age'    => 29,
            ],
        ];

        $this->createConnection($engine);
        $this->pdo->query('CREATE TABLE profiles (
        user      varchar(32)  NOT NULL,
        name      varchar(100) NOT NULL,
        gender    varchar(10) NOT NULL,
        age       int(3) NOT NULL,
        PRIMARY KEY (user)
    )')->execute();
        (new Insert('profiles', $this->pdo))
            ->rows(array_values($profiles))
            ->execute();

        $profiles = new Profile($this->pdo, []);
        $profiles->filterGender('male');
        $profiles->filterAge(30);
        $profiles->read();

        foreach ($profiles->get() as $profile) {
            $this->assertEquals('male', $profile->getter('gender'));
            $this->assertGreaterThan(30, $profile->getter('gender'));
        }
    }

    #[DataProvider('engineProvider')]
    public function testItCanLimitOrder(string $engine): void
    {
        $profiles = [
            'taylor' => [
                'user'   => 'taylor',
                'name'   => 'taylor otwell',
                'gender' => 'male',
                'age'    => 45,
            ],
            'nuno' => [
                'user'   => 'nuno',
                'name'   => 'nuno maduro',
                'gender' => 'male',
                'age'    => 40,
            ],
            'jesica' => [
                'user'   => 'jesica',
                'name'   => 'jesica w',
                'gender' => 'female',
                'age'    => 38,
            ],
            'pradana' => [
                'user'   => 'pradana',
                'name'   => 'sony pradana',
                'gender' => 'male',
                'age'    => 29,
            ],
        ];

        $this->createConnection($engine);
        $this->pdo->query('CREATE TABLE profiles (
        user      varchar(32)  NOT NULL,
        name      varchar(100) NOT NULL,
        gender    varchar(10) NOT NULL,
        age       int(3) NOT NULL,
        PRIMARY KEY (user)
    )')->execute();
        (new Insert('profiles', $this->pdo))
            ->rows(array_values($profiles))
            ->execute();

        $profiles = new Profile($this->pdo, []);
        $profiles->limitEnd(2);
        $profiles->read();

        $this->assertEquals(2, $profiles->get()->count());
    }

    #[DataProvider('engineProvider')]
    public function testItCanLimitOffset(string $engine): void
    {
        $profiles = [
            'taylor' => [
                'user'   => 'taylor',
                'name'   => 'taylor otwell',
                'gender' => 'male',
                'age'    => 45,
            ],
            'nuno' => [
                'user'   => 'nuno',
                'name'   => 'nuno maduro',
                'gender' => 'male',
                'age'    => 40,
            ],
            'jesica' => [
                'user'   => 'jesica',
                'name'   => 'jesica w',
                'gender' => 'female',
                'age'    => 38,
            ],
            'pradana' => [
                'user'   => 'pradana',
                'name'   => 'sony pradana',
                'gender' => 'male',
                'age'    => 29,
            ],
        ];

        $this->createConnection($engine);
        $this->pdo->query('CREATE TABLE profiles (
        user      varchar(32)  NOT NULL,
        name      varchar(100) NOT NULL,
        gender    varchar(10) NOT NULL,
        age       int(3) NOT NULL,
        PRIMARY KEY (user)
    )')->execute();
        (new Insert('profiles', $this->pdo))
            ->rows(array_values($profiles))
            ->execute();

        $profiles = new Profile($this->pdo, []);
        $profiles->limitOffset(1, 2);
        $profiles->read();

        $this->assertEquals(1, $profiles->get()->count());
    }

    #[DataProvider('engineProvider')]
    public function testItCanShortOrder(string $engine): void
    {
        $profiles = [
            'taylor' => [
                'user'   => 'taylor',
                'name'   => 'taylor otwell',
                'gender' => 'male',
                'age'    => 45,
            ],
            'nuno' => [
                'user'   => 'nuno',
                'name'   => 'nuno maduro',
                'gender' => 'male',
                'age'    => 40,
            ],
            'jesica' => [
                'user'   => 'jesica',
                'name'   => 'jesica w',
                'gender' => 'female',
                'age'    => 38,
            ],
            'pradana' => [
                'user'   => 'pradana',
                'name'   => 'sony pradana',
                'gender' => 'male',
                'age'    => 29,
            ],
        ];

        $this->createConnection($engine);
        $this->pdo->query('CREATE TABLE profiles (
        user      varchar(32)  NOT NULL,
        name      varchar(100) NOT NULL,
        gender    varchar(10) NOT NULL,
        age       int(3) NOT NULL,
        PRIMARY KEY (user)
    )')->execute();
        (new Insert('profiles', $this->pdo))
            ->rows(array_values($profiles))
            ->execute();

        $profiles = new Profile($this->pdo, []);

        $profiles->order('user', Query::ORDER_ASC);
        $profiles->read();
        $this->assertEquals([
            'user'   => 'jesica',
            'name'   => 'jesica w',
            'gender' => 'female',
            'age'    => 38,
        ], $profiles->first());
    }
}
