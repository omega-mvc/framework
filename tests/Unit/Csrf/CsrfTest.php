<?php

/**
 * Part of Omega - Tests\Csrf Package.
 * @link https://omega-mvc.github.io
 * @author Adriano Giovannini <agisoftt@gmail.com>
 * @copyright Copyright (c) 2025 - 2026 Adriano Giovannini (https://omega-mvc.github.io)
 * @license https://www.gnu.org/licenses/gpl-3.0-standalone.html     GPL V3.0+
 * @version 2.0.0
 */

declare(strict_types=1);

namespace Tests\Csrf;

use Omega\Csrf\Csrf;
use Omega\Csrf\CsrfInterface;
use Omega\Session\SessionManager;
use Omega\Session\Storage\ArrayStorage;

use function class_implements;
use function expect;
use function in_array;
use function preg_match;
use function str_repeat;

covers(ArrayStorage::class);
covers(Csrf::class);
covers(SessionManager::class);

beforeEach(function (): void {
    $this->session = new SessionManager('array', new ArrayStorage());
    $this->csrf    = new Csrf($this->session);
});

it('generates a 64-character hexadecimal token and stores it in the session', function (): void {
    $token = $this->csrf->generateToken();

    expect(preg_match('/^[0-9a-f]{64}$/', $token))->toBe(1);
    expect($this->session->get('_csrf.token'))->toBe($token);
});

it('generates a distinct token on each call', function (): void {
    $first  = $this->csrf->generateToken();
    $second = $this->csrf->generateToken();

    expect($second)->not->toBe($first);
});

it('validates a token issued by generateToken', function (): void {
    $token = $this->csrf->generateToken();

    expect($this->csrf->validateToken($token))->toBeTrue();
});

it('rejects a token that does not match the stored one', function (): void {
    $this->csrf->generateToken();

    expect($this->csrf->validateToken(str_repeat('0', 64)))->toBeFalse();
});

it('rejects null and empty tokens', function (): void {
    expect($this->csrf->validateToken(null))->toBeFalse();
    expect($this->csrf->validateToken(''))->toBeFalse();
});

it('rejects validation when the session holds no token', function (): void {
    expect($this->csrf->validateToken(str_repeat('0', 64)))->toBeFalse();
});

it('returns the stored token without generating a new one', function (): void {
    $stored = $this->csrf->generateToken();

    expect($this->csrf->getToken())->toBe($stored);
});

it('generates a token when none is stored and persists it', function (): void {
    $token = $this->csrf->getToken();

    expect(preg_match('/^[0-9a-f]{64}$/', $token))->toBe(1);
    expect($this->session->get('_csrf.token'))->toBe($token);
});

it('exposes the token field and token form name', function (): void {
    expect($this->csrf->getTokenField())->toBe('_csrf_token');
    expect($this->csrf->getTokenName())->toBe('_csrf.token');
});

it('implements the CsrfInterface contract', function (): void {
    expect(in_array(CsrfInterface::class, class_implements(Csrf::class), true))->toBeTrue();
});