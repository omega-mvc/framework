<?php

declare(strict_types=1);

namespace Tests\Database\Model;

use Omega\Database\Model\Model;
use Omega\Database\Query\Query;
use Omega\Database\Query\Insert;
use PHPUnit\Framework\Attributes\CoversClass;
use Tests\Database\AbstractTestDatabase;
use Tests\Database\Support\Order;
use Tests\Database\Support\User;

#[CoversClass(Model::class)]
#[CoversClass(Query::class)]
#[CoversClass(Insert::class)]
final class BaseMultiModelTest extends AbstractTestDatabase
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
        (new Insert('profiles', $this->pdo))
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
        (new Insert('orders', $this->pdo))
            ->rows($orders)
            ->execute();
    }

    /**
     * @test
     *
     * @group database
     */
    public function testItCanReadData(): void
    {
        $user = new User($this->pdo, [[]]);

        $this->assertTrue($user->read());
    }

    /**
     * @test
     *
     * @group database
     */
    public function testItCanUpdateData(): void
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
    public function testItCanDeleteData(): void
    {
        $user = $this->users();
        $this->assertTrue($user->delete());
    }

    /**
     * @test
     *
     * @group database
     */
    public function testItCanGetFirst(): void
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

        $user   = $this->users();
        $this->assertEquals($profile, $user->profile()->first());
    }

    /**
     * @test
     *
     * @group database
     */
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

        $user   = $this->users();
        $this->assertEquals($profile, $user->profile);
    }

    /**
     * @test
     *
     * @group database
     */
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

        $user   = $this->users();
        $result = $user->hasMany(Order::class, 'user');
        $this->assertEquals($order, $result->toArrayArray());
    }

    /**
     * @test
     *
     * @group database
     */
    public function testItCanCheckisClean(): void
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
    public function testItCanCheckisDirty(): void
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
    public function testItCanGetChangeColumn(): void
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
    public function testItCanHiddeColumn(): void
    {
        $user = $this->users();

        $this->assertArrayNotHasKey('password', $user->first(), 'password must hidden by stash');
    }

    /**
     * @test
     *
     * @group database
     */
    public function testItCanConvertToArray(): void
    {
        $user = $this->users();

        $this->assertEquals([
            [
                'user' => 'taylor',
                'stat' => 100,
            ],
        ], $user->toArray());
    }

    // getter setter - should return firts query

    /**
     * @test
     *
     * @group database
     */
    public function testItCanGetUsingGetterInColumn(): void
    {
        $user = $this->users();

        $columns = $user->toArray();
        $this->assertEquals($columns[0]['stat'], $user->getter('stat', 0));
    }

    /**
     * @test
     *
     * @group database
     */
    public function testItCanSetUsingSetterterInColumn(): void
    {
        $user = $this->users();

        $user->setter('stat', 80);
        $columns = $user->toArray();
        $this->assertEquals(80, $columns[0]['stat']);
    }

    /**
     * @test
     *
     * @group database
     */
    public function testItCanCheckExist(): void
    {
        $user = $this->users();

        $this->assertTrue($user->has('user'));
    }

    /**
     * @test
     *
     * @group database
     */
    public function testItCanGetUsingMagicGetterInColumn(): void
    {
        $user = $this->users();

        $columns = $user->toArray();
        $this->assertEquals($columns[0]['stat'], $user->stat);
    }

    /**
     * @test
     *
     * @group database
     */
    public function testItCanSetUsingMagicSetterterInColumn(): void
    {
        $user = $this->users();

        $user->stat = 80;
        $columns = $user->toArray();
        $this->assertEquals(80, $columns[0]['stat']);
    }

    // array access

    /**
     * @test
     *
     * @group database
     */
    public function testItCanGetUsingArray(): void
    {
        $user = $this->users();

        $columns = $user->toArray();
        $this->assertEquals($columns[0]['stat'], $user['stat']);
    }

    /**
     * @test
     *
     * @group database
     */
    public function testItCanSetUsingArray(): void
    {
        $user = $this->users();

        $user['stat'] = 80;
        $columns = $user->toArray();
        $this->assertEquals(80, $columns[0]['stat']);
    }

    /**
     * @test
     *
     * @group database
     */
    public function testItCanCheckUsingMagicIsset(): void
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
    public function testItCanUnsetUsingArray(): void
    {
        $user = $this->users();

        unset($user['stat']);
        $columns = $user->toArray();
        $this->assertEquals(100, $columns[0]['stat']);
    }

    // still can get collection

    /**
     * @test
     *
     * @group database
     */
    public function testItCanGetCollection(): void
    {
        $user = $this->users();

        $columns = $user->toArray();
        $models  = $user->get()->toArray();

        // tranform to column
        $arr = [];
        foreach ($models as $new) {
            $arr[] = $new->toArray()[0];
        }
        $this->assertEquals($columns, $arr);
    }

    // find user by some condition (static)

    /**
     * @test
     *
     * @group database
     */
    public function testItCanFindUsingId(): void
    {
        $user = User::find('taylor', $this->pdo);

        $this->assertTrue($user->has('user'));
    }

    /**
     * @test
     *
     * @group database
     */
    public function testItCanFindUsingWhere(): void
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
    public function testItCanFindUsingEqual(): void
    {
        $user = User::equal('user', 'taylor', $this->pdo);

        $this->assertTrue($user->has('user'));
    }

    /**
     * @test
     *
     * @group database
     */
    public function testItCanFindAll(): void
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

    /**
     * @test
     *
     * @group database
     */
    public function testItCanFindOrCreateButNotExits(): void
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
