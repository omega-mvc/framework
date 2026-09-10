<?php

declare(strict_types=1);

namespace Tests\Event\Support;

use Omega\Event\Dispatcher\DispatcherAwareTrait;

/**
 * Service that uses the dispatcher-aware trait.
 */
final class DispatcherAwareService
{
    use DispatcherAwareTrait;
}