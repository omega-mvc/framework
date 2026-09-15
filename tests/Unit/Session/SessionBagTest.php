<?php

/**
 * Part of Omega - Tests\Session Package.
 * @link https://omega-mvc.github.io
 * @author Adriano Giovannini <agisoftt@gmail.com>
 * @copyright Copyright (c) 2025 - 2026 Adriano Giovannini (https://omega-mvc.github.io)
 * @license https://www.gnu.org/licenses/gpl-3.0-standalone.html     GPL V3.0+
 * @version 2.0.0
 */

declare(strict_types=1);

namespace Tests\Session;

use Omega\Session\SessionBag;

use function expect;

covers(SessionBag::class);

it('returns the bag name', function (): void {
    $data = [];
    $bag  = new SessionBag('cart', $data);

    expect($bag->getName())->toBe('cart');
});

it('stores, reads and checks values with a default', function (): void {
    $data = [];
    $bag  = new SessionBag('cart', $data);

    expect($bag->has('item'))->toBeFalse();

    $bag->put('item', 'sku-1');

    expect($bag->has('item'))->toBeTrue();
    expect($bag->get('item'))->toBe('sku-1');
    expect($bag->get('missing', 'fallback'))->toBe('fallback');
});

it('mutates the referenced data array', function (): void {
    $data = [];
    $bag  = new SessionBag('cart', $data);

    $bag->put('item', 'sku-1');

    expect($data)->toEqual(['item' => 'sku-1']);
});

it('forgets a value', function (): void {
    $data = ['item' => 'sku-1', 'qty' => 2];
    $bag  = new SessionBag('cart', $data);

    $bag->forget('item');

    expect($bag->has('item'))->toBeFalse();
    expect($bag->has('qty'))->toBeTrue();
    expect($data)->toEqual(['qty' => 2]);
});

it('flushes all values', function (): void {
    $data = ['item' => 'sku-1'];
    $bag  = new SessionBag('cart', $data);

    $bag->flush();

    expect($bag->all())->toBeEmpty();
    expect($data)->toBeEmpty();
});

it('returns all values', function (): void {
    $data = ['a' => 1, 'b' => 2];
    $bag  = new SessionBag('cart', $data);

    expect($bag->all())->toEqual(['a' => 1, 'b' => 2]);
});