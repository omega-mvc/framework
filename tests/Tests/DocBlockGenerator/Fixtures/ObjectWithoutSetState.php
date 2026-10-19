<?php

declare(strict_types=1);

namespace Tests\DocBlockGenerator\Fixtures;

use PHPUnit\Framework\Attributes\CoversNothing;

#[CoversNothing]
class ObjectWithoutSetState
{
    public int $a = 1;
}
