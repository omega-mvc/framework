<?php

declare(strict_types=1);

namespace Tests\Config\Source;

use Omega\Config\Source\AbstractSource;
use PHPUnit\Framework\Attributes\CoversNothing;

#[CoversNothing]
class TestConfigurationSource extends AbstractSource
{
    public function fetch(): array
    {
        $content = rtrim($this->fetchContent());

        return compact('content');
    }
}
