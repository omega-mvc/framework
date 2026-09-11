<?php

namespace Tests;

use Omega\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected function tearDown(): void
    {
        if (!isset($this->app)) {
            return;
        }

        parent::tearDown();
    }
}
