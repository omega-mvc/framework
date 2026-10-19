<?php

declare(strict_types=1);

namespace Tests\Validator\Rule;

use Omega\Validator\Rule\Filter;
use Omega\Validator\Rule\FilterPool;
use PHPUnit\Framework\Attributes\CoversClass;
use Tests\TestCase;

#[CoversClass(FilterPool::class)]
final class FilterPoolTest extends TestCase
{
    public function testCanAddFilterPoolUsingMagicGet(): void
    {
        $pool = new FilterPool();

        $pool->test->trim();

        $this->assertRulePool(['test' => 'trim'], $pool->getPool());
    }

    public function testCanAddFilterPoolUsingMagicGetWithExistRule(): void
    {
        $pool = new FilterPool();

        $pool->test->trim();
        $pool->test->upper_case();

        $this->assertRulePool(['test' => 'trim|upper_case'], $pool->getPool());
    }

    public function testCanAddFilterPoolUsingMagicSet(): void
    {
        $pool = new FilterPool();

        $pool->test = 'trim';

        $this->assertRulePool(['test' => 'trim'], $pool->getPool());
    }

    public function testCanAddFilterPoolUsingMagicSetWithExistRule(): void
    {
        $pool = new FilterPool();

        $pool->test = 'trim';
        $pool->test = 'upper_case';

        $this->assertRulePool(['test' => 'trim|upper_case'], $pool->getPool());
    }

    public function testCanAddFilterPoolUsingInvoke(): void
    {
        $pool = new FilterPool();

        $pool('test')->trim();

        $this->assertRulePool(['test' => 'trim'], $pool->getPool());
    }

    public function testCanAddFilterPoolUsingInvokeMulty(): void
    {
        $pool = new FilterPool();

        $pool('test', 'test2')->trim();

        $this->assertRulePool([
            'test'  => 'trim',
            'test2' => 'trim',
        ], $pool->getPool());
    }

    public function testCanAddFilterPoolUsingInvokeWithExistRule(): void
    {
        $pool = new FilterPool();

        $pool('test', 'test2')->trim();
        $pool('test')->upper_case();
        $pool('test2')->upper_case();

        $this->assertRulePool([
            'test'  => 'trim|upper_case',
            'test2' => 'trim|upper_case',
        ], $pool->getPool());
    }

    public function testCanAddFilterPoolUsingRule(): void
    {
        $pool = new FilterPool();

        $pool->rule('test')->trim();

        $this->assertRulePool(['test' => 'trim'], $pool->getPool());
    }

    public function testCanAddFilterPoolUsingRuleMulty(): void
    {
        $pool = new FilterPool();

        $pool->rule('test', 'test2')->trim();

        $this->assertRulePool([
            'test'  => 'trim',
            'test2' => 'trim',
        ], $pool->getPool());
    }

    public function testCanAddFilterPoolUsingRuleMultyWithRuleExist(): void
    {
        $pool = new FilterPool();

        $pool->rule('test', 'test2')->trim();
        $pool->rule('test')->upper_case();
        $pool->rule('test2')->upper_case();

        $this->assertRulePool([
            'test'  => 'trim|upper_case',
            'test2' => 'trim|upper_case',
        ], $pool->getPool());
    }

    public function testCanFilterPoolOnlyAllowField(): void
    {
        $pool = new FilterPool();

        $pool->rule('test')->trim();
        $pool->rule('test2')->upper_case();
        $pool->rule('test3')->lower_case();

        $pool->only(['test', 'test3']);

        $this->assertRulePool([
            'test'  => 'trim',
            'test3' => 'lower_case',
        ], $pool->getPool());
    }

    public function testCanFilterPoolOnlyAllowFieldEmpty(): void
    {
        $pool = new FilterPool();

        $pool->rule('test')->trim();

        $pool->only([]);

        $this->assertSame([], $pool->getPool());
    }

    public function testCanFilterPoolExceptField(): void
    {
        $pool = new FilterPool();

        $pool->rule('test')->trim();
        $pool->rule('test2')->upper_case();
        $pool->rule('test3')->lower_case();

        $pool->except(['test2']);

        $this->assertRulePool([
            'test'  => 'trim',
            'test3' => 'lower_case',
        ], $pool->getPool());
    }

    public function testCanFilterPoolExceptFieldEmpty(): void
    {
        $pool = new FilterPool();

        $pool->rule('test')->trim();

        $pool->except([]);

        $this->assertRulePool(['test' => 'trim'], $pool->getPool());
    }

    public function testCanCombineFilterPool(): void
    {
        $pool  = new FilterPool();
        $pool2 = new FilterPool();

        $pool->rule('test')->trim();
        $pool2->rule('test2')->upper_case();

        $pool->combine($pool2);

        $this->assertRulePool([
            'test'  => 'trim',
            'test2' => 'upper_case',
        ], $pool->getPool());
    }

    /**
     * Assert that every expected field/rule pair is present in the filter pool.
     *
     * Mirrors Pest's `toMatchArray()`: each expected key must exist and its value
     * must be loosely equal (the pool stores {@see Filter} objects that cast to
     * their raw rule string).
     *
     * @param array<string, string>     $expected Expected field => rule pairs
     * @param array<int|string, Filter> $actual   Pool returned by FilterPool::getPool()
     */
    private function assertRulePool(array $expected, array $actual): void
    {
        foreach ($expected as $field => $rule) {
            $this->assertArrayHasKey($field, $actual);
            $this->assertEquals($rule, $actual[$field]);
        }
    }
}
