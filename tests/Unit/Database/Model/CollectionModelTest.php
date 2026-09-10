<?php

declare(strict_types=1);

namespace Tests\Database\Model;

use Tests\Database\AbstractTestDatabase;
use Tests\Database\Support\User;

final class CollectionModelTest extends AbstractTestDatabase
{
    protected function setUp(): void
    {
        $this->createConnection();
        $this->createUserSchema();
        $password = password_hash('password', PASSWORD_DEFAULT);
        $this->createUser([
            [
                'user'     => 'nuno',
                'password' => $password,
                'stat'     => 90,
            ],
            [
                'user'     => 'taylor',
                'password' => $password,
                'stat'     => 100,
            ],
            [
                'user'     => 'pradana',
                'password' => $password,
                'stat'     => 80,
            ],
        ]);
    }

    protected function tearDown(): void
    {
        $this->dropConnection();
    }

    public function users(): User
    {
        $user = new User($this->pdo, []);
        $user->read();

        return $user;
    }
    // item collection test

    /**
     * @test
     *
     * @group database
     */
    public function shouldReturnModelEveryItems(): void
    {
        $users = $this->users();

        foreach ($users->get() as $user) {
            $this->assertTrue($user->has('user'));
        }
    }

    /**
     * @test
     *
     * @group database
     */
    public function testItCanGetAllIds(): void
    {
        $users = $this->users()->get();

        $this->assertEqualsCanonicalizing(['nuno', 'taylor', 'pradana'], $users->getPrimaryKey());
    }

    /**
     * @test
     *
     * @group database
     */
    public function testItCanCheckIsClean(): void
    {
        $users = $this->users();

        $this->assertTrue($users->get()->isclean());
    }

    /**
     * @test
     *
     * @group database
     */
    public function testItCanCheckIsDirty(): void
    {
        $users = $this->users();

        $this->assertFalse($users->get()->isDirty());
    }

    // crud eager load

    /**
     * @test
     *
     * @group database
     */
    public function testItCanReadData(): void
    {
        $users = $this->users();

        foreach ($users->get() as $user) {
            $this->assertTrue($user->read());
        }
    }

    /**
     * @test
     *
     * @group database
     */
    public function testItCanUpdateData(): void
    {
        $users = $this->users();

        foreach ($users->get() as $user) {
            $user->setter('stat', 0);
            $this->assertTrue($user->update());
        }
    }

    /**
     * @test
     *
     * @group database
     */
    public function testItCanDeleteData(): void
    {
        $users = $this->users();

        foreach ($users->get() as $user) {
            $this->assertTrue($user->delete());
        }
    }

    // crud upstream

    /**
     * @test
     *
     * @group database
     */
    public function testItCanUpdateAllWithSingleQuery(): void
    {
        $update = $this->users()->get()->update([
            'stat' => 0,
        ]);

        $this->assertTrue($update);
    }

    /**
     * @test
     *
     * @group database
     */
    public function testItCanDeleteAllWithSingleQuery(): void
    {
        $delete = $this->users()->get()->delete();

        $this->assertTrue($delete);
    }
}
