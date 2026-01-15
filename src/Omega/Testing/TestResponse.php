<?php

declare(strict_types=1);

namespace Omega\Testing;

use PHPUnit\Framework\Assert;
use Omega\Http\Response;
use Omega\Testing\Traits\ResponseStatusTrait;

class TestResponse
{
    use ResponseStatusTrait;

    protected Response $response;

    public function __construct(Response $response)
    {
        $this->response = $response;
    }

    public function getContent(): string
    {
        return $this->response->getContent();
    }

    public function assertSee(string $text, string $message = ''): void
    {
        Assert::assertStringContainsString($text, $this->response->getContent(), $message);
    }

    public function assertStatusCode(int $code, string $message = ''): void
    {
        Assert::assertSame($code, $this->response->getStatusCode(), $message);
    }
}
