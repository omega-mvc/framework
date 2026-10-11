<?php

declare(strict_types=1);

namespace Tests\Database\Model;

use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Database\ManagesDatabase;
use Tests\Database\Support\User;
use Tests\TestCase;

use function password_hash;

use const PASSWORD_DEFAULT;

final class CollectionModelTest extends TestCase
{
    use ManagesDatabase;

    protected function tearDown(): void
    {
        $this->dropConnection();

        parent::tearDown();
    }

    #[DataProvider('engineProvider')]
    public function testItCanReadData(string $engine): void
    {
        $this->createConnection($engine);
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

        $users = new User($this->pdo, []);
        $users->read();

        foreach ($users->get() as $user) {
            $this->assertTrue($user->read());
        }
    }

    #[DataProvider('engineProvider')]
    public function testItCanUpdateData(string $engine): void
    {
        $this->createConnection($engine);
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

        $users = new User($this->pdo, []);
        $users->read();

        foreach ($users->get() as $user) {
            $user->setter('stat', 0);
            $this->assertTrue($user->update());
        }
    }

    #[DataProvider('engineProvider')]
    public function testItCanDeleteData(string $engine): void
    {
        $this->createConnection($engine);
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

        $users = new User($this->pdo, []);
        $users->read();

        foreach ($users->get() as $user) {
            $this->assertTrue($user->delete());
        }
    }

    #[DataProvider('engineProvider')]
    public function testItCanUpdateAllWithSingleQuery(string $engine): void
    {
        $this->createConnection($engine);
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

        $users  = new User($this->pdo, []);
        $users->read();

        $update = $users->get()->update([
            'stat' => 0,
        ]);

        $this->assertTrue($update);
    }

    #[DataProvider('engineProvider')]
    public function testItCanDeleteAllWithSingleQuery(string $engine): void
    {
        $this->createConnection($engine);
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

        $users  = new User($this->pdo, []);
        $users->read();

        $delete = $users->get()->delete();

        $this->assertTrue($delete);
    }
}
