<?php

declare(strict_types=1);

namespace Tests\Session;

use Omega\Session\SessionManager;
use Omega\Session\Storage\ArrayStorage;
use PHPUnit\Framework\Attributes\CoversClass;
use ReflectionProperty;
use Tests\TestCase;

use function preg_match;

#[CoversClass(SessionManager::class)]
final class SessionManagerTest extends TestCase
{
    private ArrayStorage $storage;

    private SessionManager $manager;

    protected function setUp(): void
    {
        parent::setUp();

        $this->storage = new ArrayStorage();
        $this->manager = new SessionManager('array', $this->storage);
        $this->manager->setId('test-session-id');
        $this->manager->start();
    }

    public function testStartsTheSessionCreatingAPersistedIdAndLoadingData(): void
    {
        $manager = new SessionManager('array', $this->storage);

        $this->assertTrue($manager->start());

        $id = $manager->getId();

        $this->assertNotNull($id);
        $this->assertSame(1, preg_match('/^[0-9a-f]{32}$/', (string) $id));

        $manager->put('theme', 'dark');
        $manager->save();

        $reloaded = new SessionManager('array', $this->storage);
        $reloaded->setId((string) $id);
        $reloaded->start();

        $this->assertSame('dark', $reloaded->get('theme'));
    }

    public function testRegistersDriversAndResolvesTheDefaultOne(): void
    {
        $this->assertSame($this->storage, $this->manager->getDriver());

        $driver = new ArrayStorage();

        $this->assertSame($this->manager, $this->manager->setDriver('cache', $driver));
    }

    public function testStoresReadsChecksForgetsAndFlushesSessionData(): void
    {
        $this->assertFalse($this->manager->has('user'));

        $this->manager->put('user', 'alice');

        $this->assertTrue($this->manager->has('user'));
        $this->assertSame('alice', $this->manager->get('user'));
        $this->assertSame('fallback', $this->manager->get('missing', 'fallback'));
        $this->assertEquals(['user' => 'alice'], $this->manager->all());

        $this->manager->forget('user');

        $this->assertFalse($this->manager->has('user'));

        $this->manager->put('a', 1);
        $this->manager->put('b', 2);
        $this->manager->flush();

        $this->assertEmpty($this->manager->all());
    }

    public function testExposesFlashedValuesToTheFollowingRequestThenExpiresThem(): void
    {
        $this->manager->flash('status', 'ok');

        $this->assertTrue($this->manager->hasFlash('status'));
        $this->assertSame('ok', $this->manager->getFlash('status'));

        $this->manager->save();

        $next = new SessionManager('array', $this->storage);
        $next->setId('test-session-id');
        $next->start();

        $this->assertTrue($next->hasFlash('status'));
        $this->assertSame('ok', $next->getFlash('status'));

        $next->save();

        $third = new SessionManager('array', $this->storage);
        $third->setId('test-session-id');
        $third->start();

        $this->assertFalse($third->hasFlash('status'));
        $this->assertFalse($third->has('status'));
        $this->assertSame('gone', $third->getFlash('status', 'gone'));
    }

    public function testFlashesAValueOnlyForTheCurrentRequest(): void
    {
        $this->manager->now('notice', 'hello');

        $this->assertTrue($this->manager->hasFlash('notice'));

        $this->manager->save();

        $next = new SessionManager('array', $this->storage);
        $next->setId('test-session-id');
        $next->start();

        $this->assertFalse($next->hasFlash('notice'));
        $this->assertSame('gone', $next->getFlash('notice', 'gone'));
    }

    public function testReflashKeepsCurrentFlashDataAvailable(): void
    {
        $this->manager->flash('alert', 'warn');
        $this->manager->reflash();

        $this->assertTrue($this->manager->hasFlash('alert'));
        $this->assertSame('warn', $this->manager->getFlash('alert'));
    }

    public function testClearsAllFlashData(): void
    {
        $this->manager->flash('a', 1);
        $this->manager->now('b', 2);

        $this->manager->clearFlash();

        $this->assertFalse($this->manager->hasFlash('a'));
        $this->assertFalse($this->manager->hasFlash('b'));
        $this->assertFalse($this->manager->has('a'));
        $this->assertFalse($this->manager->has('b'));
    }

    public function testCachesSessionBagsThatShareTheManagerData(): void
    {
        $first  = $this->manager->bag('cart');
        $second = $this->manager->bag('cart');

        $this->assertSame($first, $second);
        $this->assertSame('cart', $first->getName());

        $first->put('item', 'sku-1');

        $this->assertEquals(['item' => 'sku-1'], $this->manager->get('cart'));
    }

    public function testRegeneratesTheSessionIdWhileKeepingTheData(): void
    {
        $this->manager->put('user', 'alice');
        $previous = $this->manager->getId();

        $this->assertTrue($this->manager->regenerate());

        $this->assertNotSame($previous, $this->manager->getId());
        $this->assertSame('alice', $this->manager->get('user'));
    }

    public function testRegeneratesTheSessionIdAndDestroysThePreviousStorageEntry(): void
    {
        $this->manager->put('user', 'alice');
        $previous = (string) $this->manager->getId();
        $this->manager->save();

        $this->assertTrue($this->manager->regenerate(true));

        $this->assertFalse($this->storage->read($previous));
        $this->assertSame('alice', $this->manager->get('user'));
    }

    public function testDestroysTheSessionAndRemovesItsStoredData(): void
    {
        $id = (string) $this->manager->getId();
        $this->manager->getDriver()->write($id, '{"user":"alice"}');
        $this->manager->put('user', 'alice');

        $this->assertTrue($this->manager->destroy());

        $this->assertNull($this->manager->getId());
        $this->assertNull($this->manager->get('user'));
        $this->assertFalse($this->storage->read($id));
    }

    public function testDoesNotDestroyASessionThatHasNotStarted(): void
    {
        $manager = new SessionManager('array', $this->storage);

        $this->assertFalse($manager->destroy());

        $manager->setId('orphan-id');

        $this->assertFalse($manager->destroy());
    }

    public function testDoesNotSaveWhenTheSessionWasNeverStarted(): void
    {
        $manager = new SessionManager('array', $this->storage);
        $manager->setId('untouched-id');
        $manager->put('key', 'value');
        $manager->save();

        $this->assertFalse($this->storage->read('untouched-id'));
    }

    public function testReturnsTrueWhenTheSessionIsAlreadyStarted(): void
    {
        $this->assertTrue($this->manager->start());
    }

    public function testClearsFlashWithNoFlashedKeys(): void
    {
        $this->manager->clearFlash();

        $this->assertFalse($this->manager->hasFlash('anything'));
    }

    public function testClearsASingleFlashKey(): void
    {
        $this->manager->flash('a', 1);

        $this->manager->clearFlash();

        $this->assertFalse($this->manager->hasFlash('a'));
        $this->assertFalse($this->manager->has('a'));
    }

    public function testRemovesTheMatchingKeyFromTheNewFlashSlice(): void
    {
        $this->manager->flash('a', 1);
        $this->manager->now('a', 2);

        $this->assertTrue($this->manager->hasFlash('a'));
        $this->assertSame(2, $this->manager->getFlash('a'));

        $this->manager->save();

        $next = new SessionManager('array', $this->storage);
        $next->setId('test-session-id');
        $next->start();

        $this->assertFalse($next->hasFlash('a'));
        $this->assertNull($next->getFlash('a'));
    }

    public function testKeepsUnrelatedNewFlashKeysWhenFlashingNow(): void
    {
        $this->manager->flash('a', 1);
        $this->manager->flash('b', 3);
        $this->manager->now('a', 2);

        $this->manager->save();

        $next = new SessionManager('array', $this->storage);
        $next->setId('test-session-id');
        $next->start();

        $this->assertFalse($next->hasFlash('a'));
        $this->assertTrue($next->hasFlash('b'));
    }

    public function testFiltersBagDataToStringKeys(): void
    {
        $this->manager->put('cart', ['item' => 'sku-1', 7 => 'ignored']);

        $bag = $this->manager->bag('cart');

        $this->assertSame('sku-1', $bag->get('item'));
        $this->assertNull($bag->get('7'));
        $this->assertEquals(['item' => 'sku-1'], $this->manager->get('cart'));
    }

    public function testDoesNotRegenerateASessionWithoutAnId(): void
    {
        $manager = new SessionManager('array', $this->storage);

        $this->assertFalse($manager->regenerate());
    }

    public function testLoadsSessionDataWithoutFlashInformation(): void
    {
        $this->storage->write('seed-id', '{"user":"alice","theme":"dark"}');

        $manager = new SessionManager('array', $this->storage);
        $manager->setId('seed-id');
        $manager->start();

        $this->assertSame('alice', $manager->get('user'));
        $this->assertSame('dark', $manager->get('theme'));
        $this->assertFalse($manager->has('_flash'));
    }

    public function testDropsNonStringKeysFromLoadedSessionData(): void
    {
        $this->storage->write('seed-id', '[10,20]');

        $manager = new SessionManager('array', $this->storage);
        $manager->setId('seed-id');
        $manager->start();

        $this->assertEmpty($manager->all());
    }

    public function testResetsTheDataWhenTheStoredPayloadIsNotAJsonObject(): void
    {
        $this->storage->write('seed-id', 'null');

        $manager = new SessionManager('array', $this->storage);
        $manager->setId('seed-id');
        $manager->start();

        $this->assertEmpty($manager->all());
    }

    public function testRestoresMultipleFlashKeysFromTheStoredPayload(): void
    {
        $this->storage->write('seed-id', '{"a":"x","_flash":{"new":["n","m","k"],"old":[]}}');

        $manager = new SessionManager('array', $this->storage);
        $manager->setId('seed-id');
        $manager->start();

        $this->assertSame('x', $manager->get('a'));
        $this->assertFalse($manager->has('_flash'));
        $this->assertTrue($manager->hasFlash('n'));
        $this->assertTrue($manager->hasFlash('m'));
        $this->assertTrue($manager->hasFlash('k'));
    }

    public function testResolvesAClosureBackedDriverLazily(): void
    {
        $this->manager->setDriver('array', fn (): ArrayStorage => new ArrayStorage());

        $driver = $this->manager->getDriver();

        $this->assertInstanceOf(ArrayStorage::class, $driver);
        $this->assertSame($driver, $this->manager->getDriver());
    }

    public function testFiltersManyNewFlashKeysWhenFlashingNow(): void
    {
        $this->manager->flash('a', 1);
        $this->manager->flash('b', 2);
        $this->manager->flash('c', 3);
        $this->manager->flash('d', 4);
        $this->manager->now('b', 9);

        $this->manager->save();

        $next = new SessionManager('array', $this->storage);
        $next->setId('test-session-id');
        $next->start();

        $this->assertTrue($next->hasFlash('a'));
        $this->assertFalse($next->hasFlash('b'));
        $this->assertTrue($next->hasFlash('c'));
        $this->assertTrue($next->hasFlash('d'));
    }

    public function testFiltersMatchingAndDuplicatedKeysWhenFlashingNow(): void
    {
        $this->manager->flash('a', 1);
        $this->manager->flash('b', 2);
        $this->manager->now('a', 9);
        $this->manager->now('a', 10);

        $this->assertSame(10, $this->manager->getFlash('a'));
        $this->assertSame(2, $this->manager->getFlash('b'));

        $this->manager->save();

        $next = new SessionManager('array', $this->storage);
        $next->setId('test-session-id');
        $next->start();

        $this->assertTrue($next->hasFlash('b'));
    }

    public function testClearsAFlashedKeyMovedToTheOldSlice(): void
    {
        $this->manager->now('o', 1);

        $this->manager->clearFlash();

        $this->assertFalse($this->manager->hasFlash('o'));
        $this->assertFalse($this->manager->has('o'));
    }

    public function testClearsDuplicatedFlashKeysAcrossSlices(): void
    {
        $this->manager->flash('a', 1);
        $this->manager->now('a', 2);

        $this->manager->clearFlash();

        $this->assertFalse($this->manager->hasFlash('a'));
        $this->assertFalse($this->manager->has('a'));
    }

    public function testClearsMultipleFlashKeysSpreadAcrossBothSlices(): void
    {
        $this->manager->flash('a', 1);
        $this->manager->flash('b', 2);
        $this->manager->now('c', 3);
        $this->manager->now('d', 4);

        $this->manager->clearFlash();

        foreach (['a', 'b', 'c', 'd'] as $key) {
            $this->assertFalse($this->manager->hasFlash($key));
            $this->assertFalse($this->manager->has($key));
        }
    }

    public function testClearsFlashDataRepeatedly(): void
    {
        $this->manager->flash('a', 1);
        $this->manager->clearFlash();
        $this->manager->clearFlash();

        $this->assertFalse($this->manager->hasFlash('a'));
    }

    public function testStartsABagFromScalarSessionData(): void
    {
        $this->manager->put('cart', 'not-an-array');

        $bag = $this->manager->bag('cart');

        $this->assertSame('cart', $bag->getName());
        $this->assertEquals([], $this->manager->get('cart'));
    }

    public function testStartsABagFromAnEmptyArray(): void
    {
        $this->manager->put('cart', []);

        $bag = $this->manager->bag('cart');

        $this->assertEquals([], $bag->all());
    }

    public function testStartsABagFromASingleStringKey(): void
    {
        $this->manager->put('cart', ['item' => 'x']);

        $bag = $this->manager->bag('cart');

        $this->assertSame('x', $bag->get('item'));
    }

    public function testStartsABagFromThreeStringKeys(): void
    {
        $this->manager->put('cart', ['a' => 1, 'b' => 2, 'c' => 3]);

        $bag = $this->manager->bag('cart');

        $this->assertEquals(['a' => 1, 'b' => 2, 'c' => 3], $bag->all());
    }

    public function testStartsABagFromFourStringKeysAndDropsNumericOnes(): void
    {
        $this->manager->put('cart', [0 => 'zero', 1 => 'one', 'a' => 1, 'b' => 2]);

        $bag = $this->manager->bag('cart');

        $this->assertEquals(['a' => 1, 'b' => 2], $bag->all());
    }

    public function testStartsABagFromSeveralStringKeys(): void
    {
        $this->manager->put('cart', [
            'a' => 1,
            'b' => 2,
            'c' => 3,
            'd' => 4,
            'e' => 5,
        ]);

        $bag = $this->manager->bag('cart');

        $this->assertSame(5, $bag->get('e'));
    }

    public function testDestroysTheSessionAfterASaveAndRestartCycle(): void
    {
        $this->manager->save();
        $this->manager->start();

        $this->assertTrue($this->manager->destroy());
        $this->assertNull($this->manager->getId());
    }

    public function testDoesNotDestroyTheSessionTwice(): void
    {
        $this->assertTrue($this->manager->destroy());
        $this->assertFalse($this->manager->destroy());
    }

    public function testSavesTheSessionTwiceAcrossRestarts(): void
    {
        $this->manager->put('a', 1);
        $this->manager->save();

        $this->manager->start();
        $this->manager->save();

        $this->assertNotFalse($this->storage->read('test-session-id'));
    }

    public function testDoesNotSaveAfterTheSessionWasDestroyed(): void
    {
        $this->manager->destroy();
        $this->manager->save();

        $this->assertFalse($this->storage->read('test-session-id'));
    }

    public function testLoadsNoDataWhenTheStoredPayloadIsAnEmptyString(): void
    {
        $this->storage->write('seed-id', '');

        $manager = new SessionManager('array', $this->storage);
        $manager->setId('seed-id');
        $manager->start();

        $this->assertEmpty($manager->all());
    }

    public function testLoadsASingleSessionKey(): void
    {
        $this->storage->write('seed-id', '{"user":"alice"}');

        $manager = new SessionManager('array', $this->storage);
        $manager->setId('seed-id');
        $manager->start();

        $this->assertSame('alice', $manager->get('user'));
    }

    public function testLoadsThreeSessionKeysWithoutFlashInformation(): void
    {
        $this->storage->write('seed-id', '{"a":1,"b":2,"c":3}');

        $manager = new SessionManager('array', $this->storage);
        $manager->setId('seed-id');
        $manager->start();

        $this->assertEquals(['a' => 1, 'b' => 2, 'c' => 3], $manager->all());
    }

    public function testLoadsFiveSessionKeysWithoutFlashInformation(): void
    {
        $this->storage->write('seed-id', '{"a":1,"b":2,"c":3,"d":4,"e":5}');

        $manager = new SessionManager('array', $this->storage);
        $manager->setId('seed-id');
        $manager->start();

        $this->assertSame(1, $manager->get('a'));
        $this->assertSame(5, $manager->get('e'));
    }

    public function testRestoresAFlashSliceAlongsideSeveralSessionKeys(): void
    {
        $this->storage->write('seed-id', '{"a":1,"b":2,"c":3,"d":4,"e":5,"_flash":{"new":["x"],"old":[]}}');

        $manager = new SessionManager('array', $this->storage);
        $manager->setId('seed-id');
        $manager->start();

        $this->assertSame(1, $manager->get('a'));
        $this->assertSame(5, $manager->get('e'));
        $this->assertTrue($manager->hasFlash('x'));
    }

    public function testRestoresAFlashSliceWhenThePayloadContainsOnlyFlashData(): void
    {
        $this->storage->write('seed-id', '{"_flash":{"new":["x"],"old":[]}}');

        $manager = new SessionManager('array', $this->storage);
        $manager->setId('seed-id');
        $manager->start();

        $this->assertTrue($manager->hasFlash('x'));
        $this->assertFalse($manager->has('_flash'));
    }

    public function testRestoresAnEmptyFlashSliceAlongsideSessionKeys(): void
    {
        $this->storage->write('seed-id', '{"a":1,"_flash":{"new":[],"old":[]}}');

        $manager = new SessionManager('array', $this->storage);
        $manager->setId('seed-id');
        $manager->start();

        $this->assertSame(1, $manager->get('a'));
        $this->assertFalse($manager->hasFlash('a'));
    }

    public function testRestoresASingleNewFlashKeyFromTheStoredPayload(): void
    {
        $this->storage->write('seed-id', '{"_flash":{"new":["single"],"old":[]}}');

        $manager = new SessionManager('array', $this->storage);
        $manager->setId('seed-id');
        $manager->start();

        $this->assertTrue($manager->hasFlash('single'));
    }

    public function testExpiresASingleOldFlashKeyFromTheStoredPayload(): void
    {
        $this->storage->write('seed-id', '{"_flash":{"new":[],"old":["oldkey"]}}');

        $manager = new SessionManager('array', $this->storage);
        $manager->setId('seed-id');
        $manager->start();

        $this->assertFalse($manager->hasFlash('oldkey'));

        $manager->save();

        $next = new SessionManager('array', $this->storage);
        $next->setId('seed-id');
        $next->start();

        $this->assertFalse($next->hasFlash('oldkey'));
    }

    public function testRestoresFlashKeysIgnoringNonStringValues(): void
    {
        $this->storage->write('seed-id', '{"_flash":{"new":["x",7,false,"y"],"old":["z"]}}');

        $manager = new SessionManager('array', $this->storage);
        $manager->setId('seed-id');
        $manager->start();

        $this->assertTrue($manager->hasFlash('x'));
        $this->assertTrue($manager->hasFlash('y'));
        $this->assertFalse($manager->hasFlash('z'));
    }

    public function testLoadsAJsonObjectContainingAnArrayValue(): void
    {
        $this->storage->write('seed-id', '{"tags":["a","b"],"user":"u"}');

        $manager = new SessionManager('array', $this->storage);
        $manager->setId('seed-id');
        $manager->start();

        $this->assertEquals(['a', 'b'], $manager->get('tags'));
    }

    public function testExpiresMultipleOldFlashKeysOnTheNextRequest(): void
    {
        $this->manager->now('a', 1);
        $this->manager->now('b', 2);
        $this->manager->save();

        $next = new SessionManager('array', $this->storage);
        $next->setId('test-session-id');
        $next->start();

        $this->assertFalse($next->hasFlash('a'));
        $this->assertFalse($next->hasFlash('b'));
    }

    public function testIgnoresNonStringFlashKeysWhenRestoringTheNewSlice(): void
    {
        $this->storage->write('seed-id', '{"_flash":{"new":[7],"old":[]}}');

        $manager = new SessionManager('array', $this->storage);
        $manager->setId('seed-id');
        $manager->start();

        $this->assertFalse($manager->hasFlash('7'));
        $this->assertFalse($manager->hasFlash('_flash'));
    }

    public function testLoadsNoDataWhenTheStoredPayloadIsAnEmptyObject(): void
    {
        $this->storage->write('seed-id', '{}');

        $manager = new SessionManager('array', $this->storage);
        $manager->setId('seed-id');
        $manager->start();

        $this->assertEmpty($manager->all());
    }

    public function testStartsABagFromASingleNumericKey(): void
    {
        $this->manager->put('cart', [7]);

        $bag = $this->manager->bag('cart');

        $this->assertEquals([], $bag->all());
    }

    public function testDoesNotDestroyASessionWhoseIdHasBeenCleared(): void
    {
        $property = new ReflectionProperty(SessionManager::class, 'id');
        $property->setValue($this->manager, null);

        $this->assertFalse($this->manager->destroy());
    }

    public function testDoesNotSaveASessionWhoseIdHasBeenCleared(): void
    {
        $property = new ReflectionProperty(SessionManager::class, 'id');
        $property->setValue($this->manager, null);

        $this->manager->save();

        $this->assertFalse($this->storage->read('test-session-id'));
    }
}
