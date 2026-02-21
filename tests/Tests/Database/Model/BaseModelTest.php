<?php

declare(strict_types=1);

namespace Tests\Database\Model;

use Omega\Database\Model\Model;
use Omega\Database\Query\Insert;
use Tests\Database\Support\User;
use Tests\Database\AbstractTestDatabase;

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

    private function createProfiles($profiles): void
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

    private function createOrders($orders): void
    {
        new Insert('orders', $this->pdo)
            ->rows($orders)
            ->execute();
    }

    public function testItCanCreateData()
    {
        $user = new User($this->pdo, [
            [
                'user'     => 'nuno',
                'password' => password_hash('password', PASSWORD_DEFAULT),
                'stat'     => 50,
            ],
        ], [[]]);

        $this->assertTrue($user->insert());
    }

    public function testItCanReadData()
    {
        $user = new User($this->pdo, []);

        $this->assertTrue($user->read());
    }

    public function testItCanUpdateData()
    {
        $user = $this->user();

        $user->setter('stat', 75);

        $this->assertTrue($user->update());
    }

    public function testItCanDeleteData()
    {
        $user = $this->user();
        $this->assertTrue($user->delete());
    }

    public function testItCanGetFirst()
    {
        $users = $this->user();

        $this->assertEquals([
            'user' => 'taylor',
            'stat' => 100,
        ], $users->first());
    }

    public function testItCanGetHasOne()
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

    public function testItCanGetHasOneUsingMagicGetter()
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

    public function testItCanGetHasOneWithTableName()
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

    public function testItCanGetHasMany()
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

    public function testItCanGetHasManyWithMagicGetter()
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

    public function testItCanGetHasManyWithTableName()
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

    public function testItCanCheckisCleanWith()
    {
        $user = $this->user();
        $this->assertTrue($user->isClean(), 'Check all column');
        $this->assertTrue($user->isClean('stat'), 'Check spesifik column');
    }

    public function testItCanCheckisDirty()
    {
        $user = $this->user();
        $user->setter('stat', 75);
        $this->assertTrue($user->isDirty(), 'Check all column');
        $this->assertTrue($user->isDirty('stat'), 'Check spesifik column');
    }

    public function testItCanCheckColumnIsExist()
    {
        $user = $this->user();

        $this->assertTrue($user->isExist());
    }

    public function testItCanGetChangeColumn()
    {
        $user = $this->user();
        $this->assertEquals([], $user->changes(), 'original fresh data');
        // modify
        $user->setter('stat', 75);
        $this->assertEquals([
            'stat' => 75,
        ], $user->changes(), 'change first column');
    }

    public function testItCanHiddeColumn()
    {
        $user = $this->user();

        $this->assertArrayNotHasKey('password', $user->first(), 'password must hidden by stash');
    }

    public function testItCanConvertToArray()
    {
        $user = $this->user();

        $this->assertEquals([
            [
                'user' => 'taylor',
                'stat' => 100,
            ],
        ], $user->toArray());
        $this->assertIsIterable($user);
    }

    public function testItCanGetFirstPrimaryKey()
    {
        $user = $this->user();

        $this->assertEquals('taylor', $user->getPrimaryKey());
    }

    // getter setter - should return firts query

    public function testItCanGetUsingGetterInColumn()
    {
        $user = $this->user();

        $columns = (fn () => $this->{'columns'})->call($user);
        $this->assertEquals($columns[0]['stat'], $user->getter('stat', 0));
    }

    public function testItCanSetUsingSetterterInColumn()
    {
        $user = $this->user();

        $user->setter('stat', 80);
        $columns = (fn () => $this->{'columns'})->call($user);
        $this->assertEquals(80, $columns[0]['stat']);
    }

    public function testItCanCheckExist()
    {
        $user = $this->user();

        $this->assertTrue($user->has('user'));
    }

    public function testItCanGetUsingMagicGetterInColumn()
    {
        $user = $this->user();

        $columns = (fn () => $this->{'columns'})->call($user);
        $this->assertEquals($columns[0]['stat'], $user->stat);
    }

    public function testItCanSetUsingMagicSetterterInColumn()
    {
        $user = $this->user();

        $user->stat = 80;
        $columns    = (fn () => $this->{'columns'})->call($user);
        $this->assertEquals(80, $columns[0]['stat']);
    }

    public function testItCanGetUsingArray()
    {
        $user = $this->user();

        $columns = (fn () => $this->{'columns'})->call($user);
        $this->assertEquals($columns[0]['stat'], $user['stat']);
    }

    public function testItCanSetUsingArray()
    {
        $user = $this->user();

        $user['stat'] = 80;
        $columns      = (fn () => $this->{'columns'})->call($user);
        $this->assertEquals(80, $columns[0]['stat']);
    }

    public function testItCanCheckUsingMagicIsset()
    {
        $user = $this->user();
        $this->assertTrue(isset($user['user']));
    }

    public function testItCanUnsetUsingArray()
    {
        $user = $this->user();

        unset($user['stat']);
        $columns = (fn () => $this->{'columns'})->call($user);
        $this->assertEquals(100, $columns[0]['stat']);
    }

    public function testItCanGetCollection()
    {
        $user = $this->user();

        $columns = (fn () => $this->{'columns'})->call($user);
        $models  = $user->get()->toArray();

        // tranform to column
        $arr = [];
        foreach ($models as $new) {
            $arr[]= (fn () => $this->{'columns'})->call($new)[0];
        }
        $this->assertEquals($columns, $arr);
    }

    public function testItCanFindUsingId()
    {
        $user = User::find('taylor', $this->pdo);

        $this->assertTrue($user->has('user'));
    }

    public function testItCanFindUsingWhere()
    {
        $user = User::where('user = :user', [
            'user' => 'taylor',
        ], $this->pdo);

        $this->assertTrue($user->has('user'));
    }

    public function testItCanFindUsingEqual()
    {
        $user = User::equal('user', 'taylor', $this->pdo);

        $this->assertTrue($user->has('user'));
    }

    public function testItCanFindAll()
    {
        $columns = (fn () => $this->{'columns'})->call($this->user());
        $models  = User::all($this->pdo)->toArray();

        // tranform to column
        $arr = [];
        foreach ($models as $new) {
            $arr[]= (fn () => $this->{'columns'})->call($new)[0];
        }
        $this->assertEquals($columns, $arr);
    }

    public function testItCanFindOrCreate()
    {
        $user = User::findOrCreate('taylor', [
            'user'     => 'taylor',
            'password' => 'password',
            'stat'     => 100,
        ], $this->pdo);

        $this->assertTrue($user->isExist());
        $this->assertEquals('taylor', $user->getter('user', 'nuno'));
    }

    public function testItCanFindOrCreateButNotExits()
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



class Order extends Model
{
    protected string $tableName = 'orders';
}
