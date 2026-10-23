<?php

declare(strict_types=1);

namespace Tests\Csrf;

use Omega\Csrf\Csrf;
use Omega\Csrf\CsrfInterface;
use Omega\Session\SessionManager;
use Omega\Session\Storage\ArrayStorage;

use function class_implements;
use function in_array;
use function preg_match;
use function str_repeat;

use PHPUnit\Framework\Attributes\After;
use PHPUnit\Framework\Attributes\AfterClass;
use PHPUnit\Framework\Attributes\Before;
use PHPUnit\Framework\Attributes\BeforeClass;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class CsrfTest extends TestCase
{
    protected SessionManager $session;

    protected Csrf $csrf;

    protected function setUp(): void
    {
        $this->session = new SessionManager('array', new ArrayStorage());
        $this->csrf    = new Csrf($this->session);
    }

    #[Test]

    public function test_generates_a_64_character_hexadecimal_token_and_stores_it_in_the_session(): void
    {
        $token = $this->csrf->generateToken();

        $this->assertSame(1, preg_match('/^[0-9a-f]{64}$/', $token));
        $this->assertSame($token, $this->session->get('_csrf.token'));
    }

    #[Test]

    public function test_generates_a_distinct_token_on_each_call(): void
    {
        $first  = $this->csrf->generateToken();
        $second = $this->csrf->generateToken();

        $this->assertNotSame($first, $second);
    }

    #[Test]

    public function test_validates_a_token_issued_by_generateToken(): void
    {
        $token = $this->csrf->generateToken();

        $this->assertTrue($this->csrf->validateToken($token));
    }

    #[Test]

    public function test_rejects_a_token_that_does_not_match_the_stored_one(): void
    {
        $this->csrf->generateToken();

        $this->assertFalse($this->csrf->validateToken(str_repeat('0', 64)));
    }

    #[Test]

    public function test_rejects_null_and_empty_tokens(): void
    {
        $this->assertFalse($this->csrf->validateToken(null));
        $this->assertFalse($this->csrf->validateToken(''));
    }

    #[Test]

    public function test_rejects_validation_when_the_session_holds_no_token(): void
    {
        $this->assertFalse($this->csrf->validateToken(str_repeat('0', 64)));
    }

    #[Test]

    public function test_returns_the_stored_token_without_generating_a_new_one(): void
    {
        $stored = $this->csrf->generateToken();

        $this->assertSame($stored, $this->csrf->getToken());
    }

    #[Test]

    public function test_generates_a_token_when_none_is_stored_and_persists_it(): void
    {
        $token = $this->csrf->getToken();

        $this->assertSame(1, preg_match('/^[0-9a-f]{64}$/', $token));
        $this->assertSame($token, $this->session->get('_csrf.token'));
    }

    #[Test]

    public function test_exposes_the_token_field_and_token_form_name(): void
    {
        $this->assertSame('_csrf_token', $this->csrf->getTokenField());
        $this->assertSame('_csrf.token', $this->csrf->getTokenName());
    }

    #[Test]

    public function test_implements_the_CsrfInterface_contract(): void
    {
        $this->assertTrue(in_array(CsrfInterface::class, class_implements(Csrf::class), true));
    }
}
