<?php

declare(strict_types=1);

namespace Tests\Http;

use InvalidArgumentException;
use Omega\Http\Request;
use Omega\Http\Url;
use PHPUnit\Framework\Attributes\CoversClass;
use Tests\TestCase;

use function sprintf;

#[CoversClass(Url::class)]
final class UrlCoverageTest extends TestCase
{
    public function testParseRejectsUnparsableUrl(): void
    {
        $invalidUrl = 'http://:80';

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(sprintf('Unable to parse the given URL "%s".', $invalidUrl));

        Url::parse($invalidUrl);
    }

    public function testFromRequestRejectsUnparsableUrl(): void
    {
        $invalidUrl = 'http://:80';
        $request    = new Request($invalidUrl);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(sprintf('Unable to parse the URL "%s".', $invalidUrl));

        Url::fromRequest($request);
    }
}
