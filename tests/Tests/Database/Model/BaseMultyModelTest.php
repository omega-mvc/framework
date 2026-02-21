<?php

declare(strict_types=1);

namespace Tests\Database\Model;

use Omega\Database\Model\Model;
use Omega\Database\Query\Query;
use Omega\Database\Query\Insert;
use Tests\Database\AbstractTestDatabase;
use Tests\Database\Support\User;

final class BaseMultyModelTest extends AbstractTestDatabase
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

    public function users(bool $read = true): User
    {
        $user = new User($this->pdo, []);
        $user->identifier()->equal('user', 'taylor');
        if ($read) {
            $user->read();
        }

        return $user;
    }

    private function createProfileSchema(): bool
    {
        return $this
           ->pdo
           ->query('CREATE TABLE profiles (
                user      varchar(32)  NOT NULL,
                name      varchar(100) NOT NULL,
                gender    varchar(10) NOT NULL,
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

    private function createOrderSchema(): bool
    {
        return $this
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

    private function createOrders($orders): bool
    {
        return (new Insert('orders', $this->pdo))
            ->rows($orders)
            ->execute();
    }

    /**
     * @test
     *
     * @group database
     */
    public function testItCanReadData()
    {
        $user = new User($this->pdo, [[]], ['user' => ['taylor']]);

        $this->assertTrue($user->read());
    }

    /**
     * @test
     *
     * @group database
     */
    public function testItCanUpdateData()
    {
        $user = $this->users();

        $user->setter('stat', 75);

        $this->assertTrue($user->update());
    }

    /**
     * @test
     *
     * @group database
     */
    public function testItCanDeleteData()
    {
        $user = $this->users();
        $this->assertTrue($user->delete());
    }

    /**
     * @test
     *
     * @group database
     */
    public function testItCanGetFirst()
    {
        $users = $this->users();

        $this->assertEquals([
            'user' => 'taylor',
            'stat' => 100,
        ], $users->first());
    }

    /**
     * @test
     *
     * @group database
     */
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

        $user   = $this->users();
        $this->assertEquals($profile, $user->profile()->first());
    }

    /**
     * @test
     *
     * @group database
     */
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

        $user   = $this->users();
        $this->assertEquals($profile, $user->profile);
    }

    /**
     * @test
     *
     * @group database
     */
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

        $user   = $this->users();
        $result = $user->hasMany(Order::class, 'user');
        $this->assertEquals($order, $result->toArrayArray());
    }

    /**
     * @test
     *
     * @group database
     */
    public function testItCanCheckisClean()
    {
        $user = $this->users();
        $this->assertTrue($user->isClean(), 'Check all column');
        $this->assertTrue($user->isClean('stat'), 'Check spesifik column');
    }

    /**
     * @test
     *
     * @group database
     */
    public function testItCanCheckisDirty()
    {
        $user = $this->users();
        $user->setter('stat', 75);
        $this->assertTrue($user->isDirty(), 'Check all column');
        $this->assertTrue($user->isDirty('stat'), 'Check spesifik column');
    }

    /**
     * @test
     *
     * @group database
     */
    public function testItCanGetChangeColumn()
    {
        $user = $this->users();
        $this->assertEquals([], $user->changes(), 'original fresh data');
        // modify
        $user->setter('stat', 75);
        $this->assertEquals([
            'stat' => 75,
        ], $user->changes(), 'change first column');
    }

    /**
     * @test
     *
     * @group database
     */
    public function testItCanHiddeColumn()
    {
        $user = $this->users();

        $this->assertArrayNotHasKey('password', $user->first(), 'password must hidden by stash');
    }

    /**
     * @test
     *
     * @group database
     */
    public function testItCanConvertToArray()
    {
        $user = $this->users();

        $this->assertEquals([
            [
                'user' => 'taylor',
                'stat' => 100,
            ],
        ], $user->toArray());
        $this->assertIsIterable($user);
    }

    // getter setter - should return firts query

    /**
     * @test
     *
     * @group database
     */
    public function testItCanGetUsingGetterInColumn()
    {
        $user = $this->users();

        $columns = (fn () => $this->{'columns'})->call($user);
        $this->assertEquals($columns[0]['stat'], $user->getter('stat', 0));
    }

    /**
     * @test
     *
     * @group database
     */
    public function testItCanSetUsingSetterterInColumn()
    {
        $user = $this->users();

        $user->setter('stat', 80);
        $columns = (fn () => $this->{'columns'})->call($user);
        $this->assertEquals(80, $columns[0]['stat']);
    }

    /**
     * @test
     *
     * @group database
     */
    public function testItCanCheckExist()
    {
        $user = $this->users();

        $this->assertTrue($user->has('user'));
    }

    /**
     * @test
     *
     * @group database
     */
    public function testItCanGetUsingMagicGetterInColumn()
    {
        $user = $this->users();

        $columns = (fn () => $this->{'columns'})->call($user);
        $this->assertEquals($columns[0]['stat'], $user->stat);
    }

    /**
     * @test
     *
     * @group database
     */
    public function testItCanSetUsingMagicSetterterInColumn()
    {
        $user = $this->users();

        $user->stat = 80;
        $columns    = (fn () => $this->{'columns'})->call($user);
        $this->assertEquals(80, $columns[0]['stat']);
    }

    // array access

    /**
     * @test
     *
     * @group database
     */
    public function testItCanGetUsingArray()
    {
        $user = $this->users();

        $columns = (fn () => $this->{'columns'})->call($user);
        $this->assertEquals($columns[0]['stat'], $user['stat']);
    }

    /**
     * @test
     *
     * @group database
     */
    public function testItCanSetUsingArray()
    {
        $user = $this->users();

        $user['stat'] = 80;
        $columns      = (fn () => $this->{'columns'})->call($user);
        $this->assertEquals(80, $columns[0]['stat']);
    }

    /**
     * @test
     *
     * @group database
     */
    public function testItCanCheckUsingMagicIsset()
    {
        $user = $this->users();
        $this->assertTrue(isset($user['user']));
    }

    /**
     * Unset is not perform anythink.
     *
     * @test
     *
     * @group database
     */
    public function testItCanUnsetUsingArray()
    {
        $user = $this->users();

        unset($user['stat']);
        $columns = (fn () => $this->{'columns'})->call($user);
        $this->assertEquals(100, $columns[0]['stat']);
    }

    // still can get collection

    /**
     * @test
     *
     * @group database
     */
    public function testItCanGetCollection()
    {
        $user = $this->users();

        $columns = (fn () => $this->{'columns'})->call($user);
        $models  = $user->get()->toArray();

        // tranform to column
        $arr = [];
        foreach ($models as $new) {
            $arr[]= (fn () => $this->{'columns'})->call($new)[0];
        }
        $this->assertEquals($columns, $arr);
    }

    // find user by some condition (static)

    /**
     * @test
     *
     * @group database
     */
    public function testItCanFindUsingId()
    {
        $user = User::find('taylor', $this->pdo);

        $this->assertTrue($user->has('user'));
    }

    /**
     * @test
     *
     * @group database
     */
    public function testItCanFindUsingWhere()
    {
        $user = User::where('user = :user', [
            'user' => 'taylor',
        ], $this->pdo);

        $this->assertTrue($user->has('user'));
    }

    /**
     * @test
     *
     * @group database
     */
    public function testItCanFindUsingEqual()
    {
        $user = User::equal('user', 'taylor', $this->pdo);

        $this->assertTrue($user->has('user'));
    }

    /**
     * @test
     *
     * @group database
     */
    public function testItCanFindAll()
    {
        $users   = Query::from('users', $this->pdo)->select()->get()->toArray();
        $models  = User::all($this->pdo);

        $map = array_map(fn (Model $model) => $model->toArray()[0], $models->toArray());

        foreach ($users as $key => $user) {
            $this->assertEquals($user['user'], $map[$key]['user']);
        }
    }

    /**
     * @test
     *
     * @group database
     */
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

    /**
     * @test
     *
     * @group database
     */
    public function testItCanFindOrCreateButNotExits()
    {
        $user = User::findOrCreate('pradana2', [
            'user'     => 'pradana2',
            'password' => 'password',
            'stat'     => 100,
        ], $this->pdo);

        $this->assertTrue($user->isExist());
        $this->assertEquals('pradana2', $user->getter('user', 'pradana'));
    }
}
