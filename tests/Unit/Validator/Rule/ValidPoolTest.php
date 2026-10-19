<?php

declare(strict_types=1);

namespace Tests\Validator\Rule;

use Omega\Validator\Rule\Valid;
use Omega\Validator\Rule\ValidPool;
use PHPUnit\Framework\Attributes\CoversClass;
use Tests\TestCase;

#[CoversClass(ValidPool::class)]
final class ValidPoolTest extends TestCase
{
    public function testCanAddValidUsingMagicGet(): void
    {
        $pool = new ValidPool();

        $pool->test->required();

        $this->assertRulePool(['test' => 'required'], $pool->getPool());
    }

    public function testCanAddValidUsingMagicGetWithExistRule(): void
    {
        $pool = new ValidPool();

        $pool->test->required();
        $pool->test->alpha();

        $this->assertRulePool(['test' => 'required|alpha'], $pool->getPool());
    }

    public function testCanAddValidUsingMagicSet(): void
    {
        $pool = new ValidPool();

        $pool->test = 'required';

        $this->assertRulePool(['test' => 'required'], $pool->getPool());
    }

    public function testCanAddValidUsingMagicSetWithExistRule(): void
    {
        $pool = new ValidPool();

        $pool->test = 'required';
        $pool->test = 'alpha';

        $this->assertRulePool(['test' => 'required|alpha'], $pool->getPool());
    }

    public function testCanAddValidUsingInvoke(): void
    {
        $pool = new ValidPool();

        $pool('test')->required();

        $this->assertRulePool(['test' => 'required'], $pool->getPool());
    }

    public function testCanAddValidUsingInvokeWithExistRule(): void
    {
        $pool = new ValidPool();

        $pool('test')->required();
        $pool('test')->alpha();

        $this->assertRulePool(['test' => 'required|alpha'], $pool->getPool());
    }

    public function testCanAddValidUsingInvokeMulty(): void
    {
        $pool = new ValidPool();

        $pool('test', 'test2')->required();

        $this->assertRulePool([
            'test'  => 'required',
            'test2' => 'required',
        ], $pool->getPool());
    }

    public function testCanAddValidUsingRule(): void
    {
        $pool = new ValidPool();

        $pool->rule('test')->required();

        $this->assertRulePool(['test' => 'required'], $pool->getPool());
    }

    public function testCanAddValidUsingRuleWithExistRule(): void
    {
        $pool = new ValidPool();

        $pool->rule('test')->required();
        $pool->rule('test')->alpha();

        $this->assertRulePool(['test' => 'required|alpha'], $pool->getPool());
    }

    public function testCanAddValidUsingRuleMulty(): void
    {
        $pool = new ValidPool();

        $pool->rule('test', 'test2')->required();

        $this->assertRulePool([
            'test'  => 'required',
            'test2' => 'required',
        ], $pool->getPool());
    }

    /**
     * Assert that every expected field/rule pair is present in the valid pool.
     *
     * Mirrors Pest's `toMatchArray()`: each expected key must exist and its value
     * must be loosely equal (the pool stores {@see Valid} objects that cast to
     * their raw rule string).
     *
     * @param array<string, string>    $expected Expected field => rule pairs
     * @param array<int|string, Valid> $actual   Pool returned by ValidPool::getPool()
     */
    private function assertRulePool(array $expected, array $actual): void
    {
        foreach ($expected as $field => $rule) {
            $this->assertArrayHasKey($field, $actual);
            $this->assertEquals($rule, $actual[$field]);
        }
    }
}
