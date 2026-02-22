<?php

declare(strict_types=1);

namespace Tests\Database\Model;

use Omega\Database\Model\Model;
use Omega\Database\Query\Query;
use Omega\Database\Query\Insert;
use Tests\Database\AbstractTestDatabase;

final class CustomModelTest extends AbstractTestDatabase
{
    private $profiles = [
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

    protected function setUp(): void
    {
        $this->createConnection();
        $this->createProfileSchema();
        $this->createProfiles($this->profiles);
    }

    protected function tearDown(): void
    {
        $this->dropConnection();
    }

    private function createProfileSchema(): bool
    {
        return $this
           ->pdo
           ->query('CREATE TABLE profiles (
                user      varchar(32)  NOT NULL,
                name      varchar(100) NOT NULL,
                gender    varchar(10) NOT NULL,
                age       int(3) NOT NULL,
                PRIMARY KEY (user)
            )')
           ->execute();
    }

    private function createProfiles($profiles): bool
    {
        return (new Insert('profiles', $this->pdo))
            ->rows($profiles)
            ->execute();
    }

    private function profiles(): Profile
    {
        return new Profile($this->pdo, []);
    }

    /**
     * This test check for get collecion with some filter (single).
     *
     * @test
     *
     * @group database
     */
    public function testItCanFilterModel(): void
    {
        $profiles = $this->profiles();
        $profiles->filterGender('male');
        $profiles->read();

        foreach ($profiles->get() as $profile) {
            $this->assertEquals('male', $profile->getter('gender'));
        }
    }

    /**
     * This test check for get collecion with some filter (multy).
     *
     * @test
     *
     * @group database
     */
    public function testItCanFilterModelChain(): void
    {
        $profiles = $this->profiles();
        $profiles->filterGender('male');
        $profiles->filterAge(30);
        $profiles->read();

        foreach ($profiles->get() as $profile) {
            $this->assertEquals('male', $profile->getter('gender'));
            $this->assertGreaterThan(30, $profile->getter('gender'));
        }
    }

    /**
     * @test
     *
     * @group database
     */
    public function testItCanlimitOrder(): void
    {
        $profiles = $this->profiles();
        $profiles->limitEnd(2);
        $profiles->read();

        $this->assertEquals(2, $profiles->get()->count());
    }

    /**
     * @test
     *
     * @group database
     */
    public function testItCanlimitOffset(): void
    {
        $profiles = $this->profiles();
        $profiles->limitOffset(1, 2);
        $profiles->read();

        $this->assertEquals(1, $profiles->get()->count());
    }

    /**
     * @test
     *
     * @group database
     */
    public function testItCanShortOrder(): void
    {
        $profiles = $this->profiles();

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

class Profile extends Model
{
    protected string $tableName  = 'profiles';
    protected string $primaryKey = 'user';

    public function filterGender(string $gender): static
    {
        $this->where->equal('gender', $gender);

        return $this;
    }

    public function filterAge(int $greade_that): static
    {
        $this->where->compare('age', '>', $greade_that);

        return $this;
    }
}
