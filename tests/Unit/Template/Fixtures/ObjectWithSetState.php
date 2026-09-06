<?php

declare(strict_types=1);

namespace Tests\Template\Fixtures;

use PHPUnit\Framework\Attributes\CoversNothing;

#[CoversNothing]
class ObjectWithSetState
{
    public $a = 1;
    public $b = 2;

    public static function __set_state($an_array)
    {
        $obj    = new static();
        $obj->a = $an_array['a'];
        $obj->b = $an_array['b'];

        return $obj;
    }
}
