<?php

/**
 * Part of Omega - Queue Package.
 *
 * @link      https://omega-mvc.github.io
 * @author    Adriano Giovannini <agisoftt@gmail.com>
 * @copyright Copyright (c) 2025 - 2026 Adriano Giovannini (https://omega-mvc.github.io)
 * @license   https://www.gnu.org/licenses/gpl-3.0-standalone.html     GPL V3.0+
 * @version   2.0.0
 */

declare(strict_types=1);

namespace Omega\Queue\Facade;

use Closure;
use Omega\Facade\AbstractFacade;
use Omega\Queue\Job;
use Throwable;

/**
 * Facade for the queue service.
 *
 * @category   Omega
 * @package    Queue
 * @subpackage Facade
 * @link       https://omega-mvc.github.io
 * @author     Adriano Giovannini <agisoftt@gmail.com>
 * @copyright  Copyright (c) 2025 - 2026 Adriano Giovannini (https://omega-mvc.github.io)
 * @license    https://www.gnu.org/licenses/gpl-3.0-standalone.html     GPL V3.0+
 * @version    2.0.0
 *
 * @method static Job    push(Closure $job, array<string, mixed> $data = [], ?string $queue = null, int $delay = 0)
 * @method static ?Job   shift(?string $queue = null)
 * @method static int    size(?string $queue = null)
 * @method static bool   delete(Job $job)
 * @method static bool   release(Job $job, int $delay = 0)
 * @method static void   failed(Job $job, Throwable $exception)
 *
 * @see QueueManager
 */
final class Queue extends AbstractFacade
{
    public static function getFacadeAccessor(): string
    {
        return 'queue';
    }
}
