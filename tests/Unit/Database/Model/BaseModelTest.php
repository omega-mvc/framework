<?php

declare(strict_types=1);

namespace Tests\Database\Model;

use Omega\Database\Query\Insert;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Database\ManagesDatabase;
use Tests\Database\Support\Order;
use Tests\Database\Support\Profile;
use Tests\Database\Support\User;
use Tests\TestCase;

use function password_hash;

use const PASSWORD_DEFAULT;

#[CoversClass(Insert::class)]
final class BaseModelTest extends TestCase
{
    use ManagesDatabase;

    protected function tearDown(): void
    {
        $this->dropConnection();

        parent::tearDown();
    }

    #[DataProvider('engineProvider')]
    public function testItCanCreateData(string $engine): void
    {
        $this->createConnection($engine);
        $this->createUserSchema();
        $this->createUser([
            [
                'user'     => 'taylor',
                'password' => password_hash('password', PASSWORD_DEFAULT),
                'stat'     => 100,
            ],
        ]);

        $user = new User($this->pdo, [
            [
                'user'     => 'nuno',
                'password' => password_hash('password', PASSWORD_DEFAULT),
                'stat'     => 50,
            ],
        ]);

        $this->assertTrue($user->insert());
    }

    #[DataProvider('engineProvider')]
    public function testItCanReadData(string $engine): void
    {
        $this->createConnection($engine);
        $this->createUserSchema();
        $this->createUser([
            [
                'user'     => 'taylor',
                'password' => password_hash('password', PASSWORD_DEFAULT),
                'stat'     => 100,
            ],
        ]);

        $user = new User($this->pdo, []);

        $this->assertTrue($user->read());
    }

    #[DataProvider('engineProvider')]
    public function testItCanUpdateData(string $engine): void
    {
        $this->createConnection($engine);
        $this->createUserSchema();
        $this->createUser([
            [
                'user'     => 'taylor',
                'password' => password_hash('password', PASSWORD_DEFAULT),
                'stat'     => 100,
            ],
        ]);

        $user = new User($this->pdo, []);
        $user->identifier()->equal('user', 'taylor');
        $user->read();

        $user->setter('stat', 75);

        $this->assertTrue($user->update());
    }

    #[DataProvider('engineProvider')]
    public function testItCanDeleteData(string $engine): void
    {
        $this->createConnection($engine);
        $this->createUserSchema();
        $this->createUser([
            [
                'user'     => 'taylor',
                'password' => password_hash('password', PASSWORD_DEFAULT),
                'stat'     => 100,
            ],
        ]);

        $user = new User($this->pdo, []);
        $user->identifier()->equal('user', 'taylor');
        $user->read();
        $this->assertTrue($user->delete());
    }

    #[DataProvider('engineProvider')]
    public function testItCanGetFirst(string $engine): void
    {
        $this->createConnection($engine);
        $this->createUserSchema();
        $this->createUser([
            [
                'user'     => 'taylor',
                'password' => password_hash('password', PASSWORD_DEFAULT),
                'stat'     => 100,
            ],
        ]);

        $users = new User($this->pdo, []);
        $users->identifier()->equal('user', 'taylor');
        $users->read();

        $this->assertEquals([
            'user' => 'taylor',
            'stat' => 100,
        ], $users->first());
    }

    #[DataProvider('engineProvider')]
    public function testItCanGetHasOne(string $engine): void
    {
        // profile
        $profile = [
            'user'   => 'taylor',
            'name'   => 'taylor otwell',
            'gender' => 'male',
        ];
        $this->createConnection($engine);
        $this->createUserSchema();
        $this->createUser([
            [
                'user'     => 'taylor',
                'password' => password_hash('password', PASSWORD_DEFAULT),
                'stat'     => 100,
            ],
        ]);

        $this->pdo
            ->query('CREATE TABLE profiles (
            user      varchar(32)  NOT NULL,
            name      varchar(100) NOT NULL,
            gender    varchar(10) NOT NULL,
            PRIMARY KEY (user)
        )')
            ->execute()
        ;

        new Insert('profiles', $this->pdo)
            ->rows([$profile])
            ->execute();

        $user   = new User($this->pdo, []);
        $user->identifier()->equal('user', 'taylor');
        $user->read();
        $result = $user->hasOne(Profile::class, 'user');
        $this->assertEquals($profile, $result->first());
    }

    #[DataProvider('engineProvider')]
    public function testItCanGetHasOneUsingMagicGetter(string $engine): void
    {
        // profile
        $profile = [
            'user'   => 'taylor',
            'name'   => 'taylor otwell',
            'gender' => 'male',
        ];
        $this->createConnection($engine);
        $this->createUserSchema();
        $this->createUser([
            [
                'user'     => 'taylor',
                'password' => password_hash('password', PASSWORD_DEFAULT),
                'stat'     => 100,
            ],
        ]);

        $this->pdo
            ->query('CREATE TABLE profiles (
            user      varchar(32)  NOT NULL,
            name      varchar(100) NOT NULL,
            gender    varchar(10) NOT NULL,
            PRIMARY KEY (user)
        )')
            ->execute()
        ;

        new Insert('profiles', $this->pdo)
            ->rows([$profile])
            ->execute();

        $user   = new User($this->pdo, []);
        $user->identifier()->equal('user', 'taylor');
        $user->read();
        $this->assertEquals($profile, $user->profile);
    }

    #[DataProvider('engineProvider')]
    public function testItCanGetHasOneWithTableName(string $engine): void
    {
        // profile
        $profile = [
            'user'   => 'taylor',
            'name'   => 'taylor otwell',
            'gender' => 'male',
        ];
        $this->createConnection($engine);
        $this->createUserSchema();
        $this->createUser([
            [
                'user'     => 'taylor',
                'password' => password_hash('password', PASSWORD_DEFAULT),
                'stat'     => 100,
            ],
        ]);

        $this->pdo
            ->query('CREATE TABLE profiles (
            user      varchar(32)  NOT NULL,
            name      varchar(100) NOT NULL,
            gender    varchar(10) NOT NULL,
            PRIMARY KEY (user)
        )')
            ->execute()
        ;

        new Insert('profiles', $this->pdo)
            ->rows([$profile])
            ->execute();

        $user   = new User($this->pdo, []);
        $user->identifier()->equal('user', 'taylor');
        $user->read();
        $result = $user->hasOne('profiles', 'user');
        $this->assertEquals($profile, $result->first());
    }

    #[DataProvider('engineProvider')]
    public function testItCanGetHasMany(string $engine): void
    {
        // order
        $order = [
            [
                'id'     => '1',
                'user'   => 'taylor',
                'name'   => 'order 1',
                'type'   => 'gadget',
            ], [
                'id'     => '3',
                'user'   => 'taylor',
                'name'   => 'order 2',
                'type'   => 'gadget',
            ],
        ];
        $this->createConnection($engine);
        $this->createUserSchema();
        $this->createUser([
            [
                'user'     => 'taylor',
                'password' => password_hash('password', PASSWORD_DEFAULT),
                'stat'     => 100,
            ],
        ]);

        $this->pdo
            ->query('CREATE TABLE orders (
            id   varchar(3)  NOT NULL,
            user varchar(32)  NOT NULL,
            name varchar(100) NOT NULL,
            type varchar(30) NOT NULL,
            PRIMARY KEY (id)
        )')
            ->execute()
        ;

        new Insert('orders', $this->pdo)
            ->rows($order)
            ->execute();

        $user   = new User($this->pdo, []);
        $user->identifier()->equal('user', 'taylor');
        $user->read();
        $result = $user->hasMany(Order::class, 'user');
        $this->assertEquals($order, $result->toArrayArray());
    }

    #[DataProvider('engineProvider')]
    public function testItCanGetHasManyWithMagicGetter(string $engine): void
    {
        // order
        $order = [
            [
                'id'     => '1',
                'user'   => 'taylor',
                'name'   => 'order 1',
                'type'   => 'gadget',
            ], [
                'id'     => '3',
                'user'   => 'taylor',
                'name'   => 'order 2',
                'type'   => 'gadget',
            ],
        ];
        $this->createConnection($engine);
        $this->createUserSchema();
        $this->createUser([
            [
                'user'     => 'taylor',
                'password' => password_hash('password', PASSWORD_DEFAULT),
                'stat'     => 100,
            ],
        ]);

        $this->pdo
            ->query('CREATE TABLE orders (
            id   varchar(3)  NOT NULL,
            user varchar(32)  NOT NULL,
            name varchar(100) NOT NULL,
            type varchar(30) NOT NULL,
            PRIMARY KEY (id)
        )')
            ->execute()
        ;

        new Insert('orders', $this->pdo)
            ->rows($order)
            ->execute();

        $user   = new User($this->pdo, []);
        $user->identifier()->equal('user', 'taylor');
        $user->read();
        $this->assertEquals($order, $user->orders);
    }

    #[DataProvider('engineProvider')]
    public function testItCanGetHasManyWithTableName(string $engine): void
    {
        // order
        $order = [
            [
                'id'     => '1',
                'user'   => 'taylor',
                'name'   => 'order 1',
                'type'   => 'gadget',
            ], [
                'id'     => '3',
                'user'   => 'taylor',
                'name'   => 'order 2',
                'type'   => 'gadget',
            ],
        ];
        $this->createConnection($engine);
        $this->createUserSchema();
        $this->createUser([
            [
                'user'     => 'taylor',
                'password' => password_hash('password', PASSWORD_DEFAULT),
                'stat'     => 100,
            ],
        ]);

        $this->pdo
            ->query('CREATE TABLE orders (
            id   varchar(3)  NOT NULL,
            user varchar(32)  NOT NULL,
            name varchar(100) NOT NULL,
            type varchar(30) NOT NULL,
            PRIMARY KEY (id)
        )')
            ->execute()
        ;

        new Insert('orders', $this->pdo)
            ->rows($order)
            ->execute();

        $user   = new User($this->pdo, []);
        $user->identifier()->equal('user', 'taylor');
        $user->read();
        $result = $user->hasMany(Order::class, 'user');
        $this->assertEquals($order, $result->toArrayArray());
    }

    #[DataProvider('engineProvider')]
    public function testItCanGetCollection(string $engine): void
    {
        $this->createConnection($engine);
        $this->createUserSchema();
        $this->createUser([
            [
                'user'     => 'taylor',
                'password' => password_hash('password', PASSWORD_DEFAULT),
                'stat'     => 100,
            ],
        ]);

        $user   = new User($this->pdo, []);
        $user->identifier()->equal('user', 'taylor');
        $user->read();

        $columns = $user->toArray();
        $models  = $user->get()->toArray();

        // tranform to column
        $arr = [];
        foreach ($models as $new) {
            $arr[] = $new->toArray()[0];
        }
        $this->assertEquals($columns, $arr);
    }

    #[DataProvider('engineProvider')]
    public function testItCanFindUsingId(string $engine): void
    {
        $this->createConnection($engine);
        $this->createUserSchema();
        $this->createUser([
            [
                'user'     => 'taylor',
                'password' => password_hash('password', PASSWORD_DEFAULT),
                'stat'     => 100,
            ],
        ]);

        $user = User::find('taylor', $this->pdo);

        $this->assertTrue($user->has('user'));
    }

    #[DataProvider('engineProvider')]
    public function testItCanFindUsingWhere(string $engine): void
    {
        $this->createConnection($engine);
        $this->createUserSchema();
        $this->createUser([
            [
                'user'     => 'taylor',
                'password' => password_hash('password', PASSWORD_DEFAULT),
                'stat'     => 100,
            ],
        ]);

        $user = User::where('user = :user', [
            'user' => 'taylor',
        ], $this->pdo);

        $this->assertTrue($user->has('user'));
    }

    #[DataProvider('engineProvider')]
    public function testItCanFindUsingEqual(string $engine): void
    {
        $this->createConnection($engine);
        $this->createUserSchema();
        $this->createUser([
            [
                'user'     => 'taylor',
                'password' => password_hash('password', PASSWORD_DEFAULT),
                'stat'     => 100,
            ],
        ]);

        $user = User::equal('user', 'taylor', $this->pdo);

        $this->assertTrue($user->has('user'));
    }

    #[DataProvider('engineProvider')]
    public function testItCanFindAll(string $engine): void
    {
        $this->createConnection($engine);
        $this->createUserSchema();
        $this->createUser([
            [
                'user'     => 'taylor',
                'password' => password_hash('password', PASSWORD_DEFAULT),
                'stat'     => 100,
            ],
        ]);

        $user   = new User($this->pdo, []);
        $user->identifier()->equal('user', 'taylor');
        $user->read();
        $columns = $user->toArray();
        $models  = User::all($this->pdo)->toArray();

        // tranform to column
        $arr = [];
        foreach ($models as $new) {
            $arr[] = $new->toArray()[0];
        }
        $this->assertEquals($columns, $arr);
    }

    #[DataProvider('engineProvider')]
    public function testItCanFindOrCreate(string $engine): void
    {
        $this->createConnection($engine);
        $this->createUserSchema();
        $this->createUser([
            [
                'user'     => 'taylor',
                'password' => password_hash('password', PASSWORD_DEFAULT),
                'stat'     => 100,
            ],
        ]);

        $user = User::findOrCreate('taylor', [
            'user'     => 'taylor',
            'password' => 'password',
            'stat'     => 100,
        ], $this->pdo);

        $this->assertTrue($user->isExist());
        $this->assertEquals('taylor', $user->getter('user', 'nuno'));
    }

    #[DataProvider('engineProvider')]
    public function testItCanFindOrCreateButNotExits(string $engine): void
    {
        $this->createConnection($engine);
        $this->createUserSchema();
        $this->createUser([
            [
                'user'     => 'taylor',
                'password' => password_hash('password', PASSWORD_DEFAULT),
                'stat'     => 100,
            ],
        ]);

        $user = User::findOrCreate('pradana', [
            'user'     => 'pradana',
            'password' => 'password',
            'stat'     => 100,
        ], $this->pdo);

        $this->assertTrue($user->isExist());
        $this->assertEquals('pradana', $user->getter('user', 'nuno'));
    }
}
