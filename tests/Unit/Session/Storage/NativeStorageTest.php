<?php

declare(strict_types=1);

namespace Tests\Session\Storage;

use Omega\Session\Storage\NativeStorage;
use PHPUnit\Framework\Attributes\CoversClass;
use Tests\TestCase;

use function preg_match;
use function session_destroy;
use function session_id;
use function session_name;
use function session_status;

use const PHP_SESSION_ACTIVE;
use const PHP_SESSION_NONE;

#[CoversClass(NativeStorage::class)]
final class NativeStorageTest extends TestCase
{
    private NativeStorage $storage;

    protected function setUp(): void
    {
        parent::setUp();

        if (session_status() === PHP_SESSION_ACTIVE) {
            session_destroy();
        }

        session_name('PHPSESSID');
        session_id('');

        $_SESSION = [];

        $this->storage = new NativeStorage();
    }

    public function testWritesAndReadsSessionData(): void
    {
        $this->assertTrue($this->storage->write('abc', '{"foo":"bar"}'));
        $this->assertSame('{"foo":"bar"}', $this->storage->read('abc'));
    }

    public function testStoresSessionDataUnderTheDefaultSessionKey(): void
    {
        $this->storage->write('abc', '{"foo":"bar"}');

        $this->assertSame('{"foo":"bar"}', $_SESSION['omega_session']);
    }

    public function testStoresSessionDataUnderTheConfiguredPrefix(): void
    {
        $storage = new NativeStorage(['prefix' => 'app']);
        $storage->write('abc', 'data');

        $this->assertSame('data', $_SESSION['app_session']);
    }

    public function testSetsACustomSessionNameWhenConfigured(): void
    {
        new NativeStorage(['name' => 'CUSTOM_NAME']);

        $this->assertSame('CUSTOM_NAME', session_name());
    }

    public function testKeepsTheDefaultSessionNameWhenNotConfigured(): void
    {
        new NativeStorage();

        $this->assertSame('PHPSESSID', session_name());
    }

    public function testReturnsFalseWhenReadingAMissingSession(): void
    {
        $this->assertFalse($this->storage->read('missing'));
    }

    public function testReturnsFalseWhenTheStoredValueIsNotAString(): void
    {
        $this->storage->write('abc', 'x');
        $_SESSION['omega_session'] = ['nested' => true];

        $this->assertFalse($this->storage->read('abc'));
    }

    public function testReturnsFalseWhenTheStoredValueIsAnEmptyString(): void
    {
        $this->storage->write('abc', '');

        $this->assertFalse($this->storage->read('abc'));
    }

    public function testReturnsFalseForAnEmptyValueReadWhileTheSessionIsInactive(): void
    {
        $this->storage->write('abc', '');
        $this->storage->close();

        $this->assertFalse($this->storage->read('abc'));
    }

    public function testWritesIntoAnAlreadyActiveSession(): void
    {
        $this->storage->write('abc', 'first');

        $this->assertTrue($this->storage->write('def', 'second'));
        $this->assertSame('second', $_SESSION['omega_session']);
    }

    public function testDestroysAnActiveSessionAndClearsItsData(): void
    {
        $this->storage->write('abc', '{"foo":"bar"}');

        $this->assertTrue($this->storage->destroy('abc'));
        $this->assertFalse($this->storage->read('abc'));
    }

    public function testDestroysASessionStartingFromAnInactiveState(): void
    {
        $this->assertTrue($this->storage->destroy('never-started'));
        $this->assertSame(PHP_SESSION_NONE, session_status());
    }

    public function testGarbageCollectionReportsZeroDeletedEntries(): void
    {
        $this->assertSame(0, $this->storage->gc(3600));
    }

    public function testCreatesA32CharacterHexadecimalSessionId(): void
    {
        $id = $this->storage->createId();

        $this->assertSame(1, preg_match('/^[0-9a-f]{32}$/', $id));
        $this->assertNotSame($id, $this->storage->createId());
    }

    public function testClosesAnActiveSessionAndPersistsItsData(): void
    {
        $this->storage->write('abc', '{"foo":"bar"}');

        $this->assertTrue($this->storage->close());
        $this->assertSame(PHP_SESSION_NONE, session_status());
        $this->assertSame('{"foo":"bar"}', $this->storage->read('abc'));
    }

    public function testClosesCleanlyWhenNoSessionIsActive(): void
    {
        $this->assertTrue($this->storage->close());
    }
}
