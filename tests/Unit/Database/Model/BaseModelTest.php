<?php

declare(strict_types=1);

namespace Tests\Database\Model;

use Omega\Database\Query\Insert;
use PHPUnit\Framework\Attributes\CoversClass;
use Tests\Database\Support\Order;
use Tests\Database\Support\Profile;
use Tests\Database\Support\User;
use Tests\Database\AbstractTestDatabase;

#[CoversClass(Insert::class)]
final class BaseModelTest extends AbstractTestDatabase
{
    protected function setUp(): void
    {
        $this->createConnection();
        $this->createUserSchema();
        $this->createUser([
            [
                'user'     => 'taylor',
                'password' => password_hash('password', PASSWORD_DEFAULT),
                'stat'     => 100,
            ],
        ]);
    }

    protected function tearDown(): void
    {
        $this->dropConnection();
    }

    public function user(bool $read = true): User
    {
        $user = new User($this->pdo, []);
        $user->identifier()->equal('user', 'taylor');
        if ($read) {
            $user->read();
        }

        return $user;
    }

    private function createProfileSchema(): void
    {
        $this
            ->pdo
            ->query('CREATE TABLE profiles (
                user      varchar(32)  NOT NULL,
                name      varchar(100) NOT NULL,
                gender    varchar(10) NOT NULL,
                PRIMARY KEY (user)
            )')
            ->execute();
    }

    /**
     * @param array<int, array<string, bool|int|string|null>> $profiles
     */
    private function createProfiles(array $profiles): void
    {
        new Insert('profiles', $this->pdo)
            ->rows($profiles)
            ->execute();
    }

    private function createOrderSchema(): void
    {
        $this
            ->pdo
            ->query('CREATE TABLE orders (
                id   varchar(3)  NOT NULL,
                user varchar(32)  NOT NULL,
                name varchar(100) NOT NULL,
                type varchar(30) NOT NULL,
                PRIMARY KEY (id)
            )')
            ->execute();
    }

    /**
     * @param array<int, array<string, bool|int|string|null>> $orders
     */
    private function createOrders(array $orders): void
    {
        new Insert('orders', $this->pdo)
            ->rows($orders)
            ->execute();
    }

    public function testItCanCreateData(): void
    {
        $user = new User($this->pdo, [
            [
                'user'     => 'nuno',
                'password' => password_hash('password', PASSWORD_DEFAULT),
                'stat'     => 50,
            ],
        ]);

        $this->assertTrue($user->insert());
    }

    public function testItCanReadData(): void
    {
        $user = new User($this->pdo, []);

        $this->assertTrue($user->read());
    }

    public function testItCanUpdateData(): void
    {
        $user = $this->user();

        $user->setter('stat', 75);

        $this->assertTrue($user->update());
    }

    public function testItCanDeleteData(): void
    {
        $user = $this->user();
        $this->assertTrue($user->delete());
    }

    public function testItCanGetFirst(): void
    {
        $users = $this->user();

        $this->assertEquals([
            'user' => 'taylor',
            'stat' => 100,
        ], $users->first());
    }

    public function testItCanGetHasOne(): void
    {
        // profile
        $profile = [
            'user'   => 'taylor',
            'name'   => 'taylor otwell',
            'gender' => 'male',
        ];
        $this->createProfileSchema();
        $this->createProfiles([$profile]);

        $user   = $this->user();
        $result = $user->hasOne(Profile::class, 'user');
        $this->assertEquals($profile, $result->first());
    }

    public function testItCanGetHasOneUsingMagicGetter(): void
    {
        // profile
        $profile = [
            'user'   => 'taylor',
            'name'   => 'taylor otwell',
            'gender' => 'male',
        ];
        $this->createProfileSchema();
        $this->createProfiles([$profile]);

        $user   = $this->user();
        $this->assertEquals($profile, $user->profile);
    }

    public function testItCanGetHasOneWithTableName(): void
    {
        // profile
        $profile = [
            'user'   => 'taylor',
            'name'   => 'taylor otwell',
            'gender' => 'male',
        ];
        $this->createProfileSchema();
        $this->createProfiles([$profile]);

        $user   = $this->user();
        $result = $user->hasOne('profiles', 'user');
        $this->assertEquals($profile, $result->first());
    }

    public function testItCanGetHasMany(): void
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
        $this->createOrderSchema();
        $this->createOrders($order);

        $user   = $this->user();
        $result = $user->hasMany(Order::class, 'user');
        $this->assertEquals($order, $result->toArrayArray());
    }

    public function testItCanGetHasManyWithMagicGetter(): void
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
        $this->createOrderSchema();
        $this->createOrders($order);

        $user   = $this->user();
        $this->assertEquals($order, $user->orders);
    }

    public function testItCanGetHasManyWithTableName(): void
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
        $this->createOrderSchema();
        $this->createOrders($order);

        $user   = $this->user();
        $result = $user->hasMany(Order::class, 'user');
        $this->assertEquals($order, $result->toArrayArray());
    }

    public function testItCanCheckisCleanWith(): void
    {
        $user = $this->user();
        $this->assertTrue($user->isClean(), 'Check all column');
        $this->assertTrue($user->isClean('stat'), 'Check spesifik column');
    }

    public function testItCanCheckisDirty(): void
    {
        $user = $this->user();
        $user->setter('stat', 75);
        $this->assertTrue($user->isDirty(), 'Check all column');
        $this->assertTrue($user->isDirty('stat'), 'Check spesifik column');
    }

    public function testItCanCheckColumnIsExist(): void
    {
        $user = $this->user();

        $this->assertTrue($user->isExist());
    }

    public function testItCanGetChangeColumn(): void
    {
        $user = $this->user();
        $this->assertEquals([], $user->changes(), 'original fresh data');
        // modify
        $user->setter('stat', 75);
        $this->assertEquals([
            'stat' => 75,
        ], $user->changes(), 'change first column');
    }

    public function testItCanHiddeColumn(): void
    {
        $user = $this->user();

        $this->assertArrayNotHasKey('password', $user->first(), 'password must hidden by stash');
    }

    public function testItCanConvertToArray(): void
    {
        $user = $this->user();

        $this->assertEquals([
            [
                'user' => 'taylor',
                'stat' => 100,
            ],
        ], $user->toArray());
    }

    public function testItCanGetFirstPrimaryKey(): void
    {
        $user = $this->user();

        $this->assertEquals('taylor', $user->getPrimaryKey());
    }

    // getter setter - should return firts query

    public function testItCanGetUsingGetterInColumn(): void
    {
        $user = $this->user();

        $columns = $user->toArray();
        $this->assertEquals($columns[0]['stat'], $user->getter('stat', 0));
    }

    public function testItCanSetUsingSetterterInColumn(): void
    {
        $user = $this->user();

        $user->setter('stat', 80);
        $columns = $user->toArray();
        $this->assertEquals(80, $columns[0]['stat']);
    }

    public function testItCanCheckExist(): void
    {
        $user = $this->user();

        $this->assertTrue($user->has('user'));
    }

    public function testItCanGetUsingMagicGetterInColumn(): void
    {
        $user = $this->user();

        $columns = $user->toArray();
        $this->assertEquals($columns[0]['stat'], $user->stat);
    }

    public function testItCanSetUsingMagicSetterterInColumn(): void
    {
        $user = $this->user();

        $user->stat = 80;
        $columns    = $user->toArray();
        $this->assertEquals(80, $columns[0]['stat']);
    }

    public function testItCanGetUsingArray(): void
    {
        $user = $this->user();

        $columns = $user->toArray();
        $this->assertEquals($columns[0]['stat'], $user['stat']);
    }

    public function testItCanSetUsingArray(): void
    {
        $user = $this->user();

        $user['stat'] = 80;
        $columns      = $user->toArray();
        $this->assertEquals(80, $columns[0]['stat']);
    }

    public function testItCanCheckUsingMagicIsset(): void
    {
        $user = $this->user();
        $this->assertTrue(isset($user['user']));
    }

    public function testItCanUnsetUsingArray(): void
    {
        $user = $this->user();

        unset($user['stat']);
        $columns = $user->toArray();
        $this->assertEquals(100, $columns[0]['stat']);
    }

    public function testItCanGetCollection(): void
    {
        $user = $this->user();

        $columns = $user->toArray();
        $models  = $user->get()->toArray();

        // tranform to column
        $arr = [];
        foreach ($models as $new) {
            $arr[] = $new->toArray()[0];
        }
        $this->assertEquals($columns, $arr);
    }

    public function testItCanFindUsingId(): void
    {
        $user = User::find('taylor', $this->pdo);

        $this->assertTrue($user->has('user'));
    }

    public function testItCanFindUsingWhere(): void
    {
        $user = User::where('user = :user', [
            'user' => 'taylor',
        ], $this->pdo);

        $this->assertTrue($user->has('user'));
    }

    public function testItCanFindUsingEqual(): void
    {
        $user = User::equal('user', 'taylor', $this->pdo);

        $this->assertTrue($user->has('user'));
    }

    public function testItCanFindAll(): void
    {
        $columns = $this->user()->toArray();
        $models  = User::all($this->pdo)->toArray();

        // tranform to column
        $arr = [];
        foreach ($models as $new) {
            $arr[] = $new->toArray()[0];
        }
        $this->assertEquals($columns, $arr);
    }

    public function testItCanFindOrCreate(): void
    {
        $user = User::findOrCreate('taylor', [
            'user'     => 'taylor',
            'password' => 'password',
            'stat'     => 100,
        ], $this->pdo);

        $this->assertTrue($user->isExist());
        $this->assertEquals('taylor', $user->getter('user', 'nuno'));
    }

    public function testItCanFindOrCreateButNotExits(): void
    {
        $user = User::findOrCreate('pradana', [
            'user'     => 'pradana',
            'password' => 'password',
            'stat'     => 100,
        ], $this->pdo);

        $this->assertTrue($user->isExist());
        $this->assertEquals('pradana', $user->getter('user', 'nuno'));
    }
}
