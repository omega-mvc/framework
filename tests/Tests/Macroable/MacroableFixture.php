<?php

declare(strict_types=1);

namespace Tests\Macroable;

use Omega\Macroable\Exceptions\MacroNotFoundException;
use Omega\Macroable\MacroableTrait;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversTrait;

/**
 * @method bool test()
 * @method bool test_param(bool $bool)
 * @method static bool test()
 * @method static bool test_param(bool $bool)
 */
#[CoversClass(MacroNotFoundException::class)]
#[CoversTrait(MacroableTrait::class)]
class MacroableFixture
{
    use MacroableTrait;
}
