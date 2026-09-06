<?php

declare(strict_types=1);

namespace Tests\Template\Fixtures;

use PHPUnit\Framework\Attributes\CoversNothing;

#[CoversNothing]
class ObjectWithVisibility
{
    public $public       = 1;
    protected $protected = 2;
    private $private     = 3;

    public static function __set_state($array)
    {
        $obj            = new self();
        $obj->public    = $array['public'];
        $obj->protected = $array['protected'];
        $obj->private   = $array['private'];

        return $obj;
    }

    public function getPublic(): int
    {
        return $this->public;
    }

    public function getProtected(): int
    {
        return $this->protected;
    }

    public function getPrivate(): int
    {
        return $this->private;
    }
}
