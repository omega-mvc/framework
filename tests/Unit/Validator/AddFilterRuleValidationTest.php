<?php

declare(strict_types=1);

namespace Tests\Validator;

use Omega\Validator\Rule\FilterPool;
use Omega\Validator\Validator;
use Tests\TestCase;

final class AddFilterRuleValidationTest extends TestCase
{
    public function testCanAddFilterRule(): void
    {
        $validation = new Validator(['test' => 'test']);
        $validation->filter('test')->upper_case();

        $this->assertEquals('TEST', $validation->filters->get('test'));
    }

    public function testCanAddFilterRuleUsingMethodFiltersParam(): void
    {
        $valid = new Validator(['test' => ' test ', 'test2' => ' test ']);

        $valid->field('test', 'test2')->required();
        $valid->filters(fn (FilterPool $f): array => [
            $f->test->trim(),
            $f->test2->trim(),
        ]);

        $filters = $valid->filterOut();

        $this->assertEquals('test', $filters['test']);
        $this->assertEquals('test', $filters['test2']);
    }

    public function testCanAddFilterRuleUsingMethodFiltersReturn(): void
    {
        $valid = new Validator(['test' => ' test ', 'test2' => ' test ']);

        $valid->field('test', 'test2')->required();
        $valid->filters(function (): FilterPool {
            $f = new FilterPool();
            $f->test->trim();
            $f->test2->trim();

            return $f;
        });

        $filters = $valid->filterOut();

        $this->assertEquals('test', $filters['test']);
        $this->assertEquals('test', $filters['test2']);
    }

    public function testCanAddNewFilterWithExistRule(): void
    {
        $valid = new Validator(['test' => ' test ']);

        $valid->field('test')->required();

        $valid->filter('test')->trim();
        $valid->filter('test')->upper_case();

        $filters = $valid->filterOut();

        $this->assertEquals('TEST', $filters['test']);
    }

    public function testCanAddNewFilterWithExistRuleUsingMethodFilters(): void
    {
        $valid = new Validator(['test' => ' test ']);

        $valid->field('test')->required();

        $valid->filters(fn (FilterPool $filter): array => [
            $filter('test')->trim(),
            $filter('test')->upper_case(),
        ]);

        $filters = $valid->filterOut();

        $this->assertEquals('TEST', $filters['test']);
    }

    public function testCanAddFilterRuleUsingPoolsCallbackFromMethodMakeParam(): void
    {
        $v = Validator::make(
            [
                'test' => 'test',
            ],
            null,
            fn (FilterPool $f): array => [
                $f('test')->upper_case(),
            ]
        );

        $this->assertEquals('TEST', $v->filters->get('test'));
    }

    public function testCanAddFilterRuleUsingPoolsCallbackFromMethodMakeReturn(): void
    {
        $v = Validator::make(
            [
                'test' => 'Test',
            ],
            null,
            function (): FilterPool {
                $f = new FilterPool();

                $f('test')->upper_case();

                return $f;
            }
        );

        $this->assertEquals('TEST', $v->filters->get('test'));
    }

    public function testCanAddMultyFilterUsingMethodFilter(): void
    {
        $valid = new Validator(['test' => ' test ', 'test2' => ' test ']);

        $valid->field('test', 'test2')->required();
        $valid->filter('test', 'test2')->trim();

        $filters = $valid->filterOut();

        $this->assertEquals('test', $filters['test']);
        $this->assertEquals('test', $filters['test2']);
    }

    public function testCanAddMultyFilterUsingMethodFilterWithFilterExist(): void
    {
        $valid = new Validator(['test' => ' test? ', 'test2' => ' test ']);

        $valid->field('test', 'test2')->required();
        $valid->filter('test', 'test2')->trim();
        $valid->filter('test')->upper_case();
        $valid->filter('test2')->upper_case();
        $valid->filter('test', 'test2')->rmpunctuation();

        $filters = $valid->filterOut();

        $this->assertEquals('TEST', $filters['test']);
        $this->assertEquals('TEST', $filters['test2']);
    }
}
