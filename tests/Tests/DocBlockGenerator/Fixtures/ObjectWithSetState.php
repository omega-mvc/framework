<?php

declare(strict_types=1);

namespace Tests\DocBlockGenerator\Fixtures;

use PHPUnit\Framework\Attributes\CoversNothing;

#[CoversNothing]
class ObjectWithSetState
{
    public int $a = 1;
    public int $b = 2;

    /**
     * @param array{a: int, b: int} $an_array
     */
    public static function __set_state(array $an_array): self
    {
        $obj    = new self();
        $obj->a = $an_array['a'];
        $obj->b = $an_array['b'];

        return $obj;
    }
}
