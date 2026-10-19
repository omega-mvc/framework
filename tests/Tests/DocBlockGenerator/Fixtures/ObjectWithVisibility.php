<?php

declare(strict_types=1);

namespace Tests\DocBlockGenerator\Fixtures;

use PHPUnit\Framework\Attributes\CoversNothing;

#[CoversNothing]
class ObjectWithVisibility
{
    public int $public       = 1;
    protected int $protected = 2;
    private int $private     = 3;

    /**
     * @param array{public: int, protected: int, private: int} $array
     */
    public static function __set_state(array $array): self
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
