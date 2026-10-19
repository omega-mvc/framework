<?php

declare(strict_types=1);

namespace Tests\Validator;

use Omega\Validator\Validator;
use PHPUnit\Framework\Attributes\CoversClass;
use Tests\TestCase;

#[CoversClass(Validator::class)]
final class SubmitedFormValidationTest extends TestCase
{
    public function testSubmittedFromFormNotMethod(): void
    {
        unset($_SERVER);

        $this->assertFalse(Validator::make()->submitted());
    }

    public function testSubmittedFromFormMethodGet(): void
    {
        $_SERVER['REQUEST_METHOD'] = 'GET';

        $this->assertFalse(Validator::make()->submitted());
    }

    public function testSubmittedFromFormMethodPost(): void
    {
        $_SERVER['REQUEST_METHOD'] = 'POST';

        $this->assertTrue(Validator::make()->submitted());
    }
}
