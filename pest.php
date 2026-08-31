<?php

uses()->bootstrap('tests/bootstrap.php');

uses()->in('Tests')->group('unit');

/**
 * Custom expectations are already available in Pest\Expectation:
 * - toBeArray, toBeString, toBeInt, toBeBool, toBeFloat, toBeNull
 * - toBeTrue, toBeFalse, toBeEmpty, toContain, toHaveCount
 * - toHaveKey, toMatch, etc.
 * No need to redefine them.
 */

// Coverage configuration (used when running with --coverage)
coverage()
    ->include('src')
    ->html('cache/coverage-report')
    ->minimum(80);

// Test environment variables
dataset('env', [
    'APP_ENV' => 'testing',
    'OMEGA_TEST_MODE' => 'light',
]);

// Parallel testing (requires pestphp/pest-parallel plugin)
// uses()->parallel();
