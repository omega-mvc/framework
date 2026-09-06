<?php

/**
 * Part of Omega - Tests\Application\Bootstrap Package.
 *
 * @link      https://omega-mvc.github.io
 * @author    Adriano Giovannini <agisoftt@gmail.com>
 * @copyright Copyright (c) 2025 - 2026 Adriano Giovannini (https://omega-mvc.github.io)
 * @license   https://www.gnu.org/licenses/gpl-3.0-standalone.html     GPL V3.0+
 * @version   2.0.0
 */

declare(strict_types=1);

namespace Tests\Application\Bootstrapper\Fixtures;

use Omega\Container\AbstractServiceProvider;
use PHPUnit\Framework\Attributes\CoversClass;

/**
 * Class BootCalledServiceProvider
 *
 * A minimal service provider used only to track whether its ``boot()`` method
 * has been invoked. It is used by the boot provider tests to confirm that the
 * application boots a provider immediately when it is already booted, and that
 * it waits for the boot phase otherwise.
 *
 * @category   Tests
 * @package    Application
 * @subpackage Bootstrap
 * @link       https://omega-mvc.github.io
 * @author     Adriano Giovannini <agisoftt@gmail.com>
 * @copyright  Copyright (c) 2025 - 2026 Adriano Giovannini (https://omega-mvc.github.io)
 * @license    https://www.gnu.org/licenses/gpl-3.0-standalone.html     GPL V3.0+
 * @version    2.0.0
 */
#[CoversClass(AbstractServiceProvider::class)]
final class BootCalledServiceProvider extends AbstractServiceProvider
{
    public bool $bootCalled = false;

    public function register(): void
    {
    }

    public function boot(): void
    {
        $this->bootCalled = true;
    }
}
