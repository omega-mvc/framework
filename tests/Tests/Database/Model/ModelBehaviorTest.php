<?php

declare(strict_types=1);

namespace Tests\Database\Model;

use Omega\Database\ConnectionInterface;
use Omega\Database\Model\Model;
use Omega\Database\Model\ModelCollection;
use PHPUnit\Framework\Attributes\CoversClass;
use Tests\Database\Support\User;
use Tests\TestCase;

#[CoversClass(Model::class)]
#[CoversClass(ModelCollection::class)]
final class ModelBehaviorTest extends TestCase
{
    private ConnectionInterface $connection;

    protected function setUp(): void
    {
        parent::setUp();

        $this->connection = $this->createStub(ConnectionInterface::class);
    }

    public function testItCanCheckisClean(): void
    {
        $user = self::user($this->connection, self::singleUser());

        $this->assertTrue($user->isClean());
        $this->assertTrue($user->isClean('stat'));
    }

    public function testItCanCheckisDirty(): void
    {
        $user = self::user($this->connection, self::singleUser());
        $user->setter('stat', 75);

        $this->assertTrue($user->isDirty());
        $this->assertTrue($user->isDirty('stat'));
    }

    public function testItCanGetChangeColumn(): void
    {
        $user = self::user($this->connection, self::singleUser());
        $this->assertEquals([], $user->changes());

        $user->setter('stat', 75);
        $this->assertEquals([
            'stat' => 75,
        ], $user->changes());
    }

    public function testItCanHiddeColumn(): void
    {
        $user = self::user($this->connection, self::singleUser());

        $this->assertArrayNotHasKey('password', $user->first());
    }

    public function testItCanGetPrimaryKey(): void
    {
        $user = self::user($this->connection, self::singleUser());

        $this->assertEquals('taylor', $user->getPrimaryKey());
    }

    public function testItCanConvertToArray(): void
    {
        $user = self::user($this->connection, self::singleUser());

        $this->assertEquals([
            [
                'user' => 'taylor',
                'stat' => 100,
            ],
        ], $user->toArray());
    }

    public function testItCanGetUsingGetterInColumn(): void
    {
        $user = self::user($this->connection, self::singleUser());
        $this->assertEquals(100, $user->getter('stat', 0));
    }

    public function testItCanSetUsingSetterterInColumn(): void
    {
        $user = self::user($this->connection, self::singleUser());
        $user->setter('stat', 80);
        $columns = $user->toArray();
        $this->assertEquals(80, $columns[0]['stat']);
    }

    public function testItCanCheckExist(): void
    {
        $user = self::user($this->connection, self::singleUser());

        $this->assertTrue($user->has('user'));
    }

    public function testItCanGetUsingMagicGetterInColumn(): void
    {
        $user = self::user($this->connection, self::singleUser());
        $this->assertEquals(100, $user->stat);
    }

    public function testItCanSetUsingMagicSetterterInColumn(): void
    {
        $user = self::user($this->connection, self::singleUser());
        $user->stat = 80;
        $columns = $user->toArray();
        $this->assertEquals(80, $columns[0]['stat']);
    }

    public function testItCanGetUsingArray(): void
    {
        $user = self::user($this->connection, self::singleUser());
        $this->assertEquals(100, $user['stat']);
    }

    public function testItCanSetUsingArray(): void
    {
        $user = self::user($this->connection, self::singleUser());
        $user['stat'] = 80;
        $columns = $user->toArray();
        $this->assertEquals(80, $columns[0]['stat']);
    }

    public function testItCanCheckUsingMagicIsset(): void
    {
        $user = self::user($this->connection, self::singleUser());
        $this->assertTrue(isset($user['user']));
    }

    public function testItCanUnsetUsingArray(): void
    {
        $user = self::user($this->connection, self::singleUser());

        unset($user['stat']);
        $columns = $user->toArray();
        $this->assertEquals(100, $columns[0]['stat']);
    }

    public function testItCanConvertCollectionToModelEveryItems(): void
    {
        $user = self::user($this->connection, self::multiUser());

        foreach ($user->get() as $model) {
            $this->assertTrue($model->has('user'));
        }
    }

    public function testItCanGetAllPrimaryKeysFromCollection(): void
    {
        $user = self::user($this->connection, self::multiUser());

        $this->assertEqualsCanonicalizing(['nuno', 'taylor', 'pradana'], $user->get()->getPrimaryKey());
    }

    public function testItCanCheckCollectionIsClean(): void
    {
        $user = self::user($this->connection, self::multiUser());

        $this->assertTrue($user->get()->isClean());
    }

    public function testItCanCheckCollectionIsDirty(): void
    {
        $user = self::user($this->connection, self::multiUser());

        $this->assertFalse($user->get()->isDirty());
    }

    /**
     * Build a single-row User model without touching any database engine.
     *
     * @param array<int, array<string, bool|int|string|null>> $rows
     * @return User
     */
    private static function user(ConnectionInterface $connection, array $rows): User
    {
        return new User($connection, $rows);
    }

    /**
     * @return array<int, array<string, bool|int|string|null>>
     */
    private static function singleUser(): array
    {
        return [
            [
                'user'     => 'taylor',
                'password' => 'secret',
                'stat'     => 100,
            ],
        ];
    }

    /**
     * @return array<int, array<string, bool|int|string|null>>
     */
    private static function multiUser(): array
    {
        return [
            [
                'user'     => 'nuno',
                'password' => 'secret',
                'stat'     => 90,
            ],
            [
                'user'     => 'taylor',
                'password' => 'secret',
                'stat'     => 100,
            ],
            [
                'user'     => 'pradana',
                'password' => 'secret',
                'stat'     => 80,
            ],
        ];
    }
}
