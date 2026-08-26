<?php

/**
 * Part of Omega - Event Package.
 *
 * @link      https://omega-mvc.github.io
 * @author    Adriano Giovannini <agisoftt@gmail.com>
 * @copyright Copyright (c) 2025 - 2026 Adriano Giovannini (https://omega-mvc.github.io)
 * @license   https://www.gnu.org/licenses/gpl-3.0-standalone.html     GPL V3.0+
 * @version   2.0.0
 */

declare(strict_types=1);

namespace Omega\Event;

use Omega\Container\AbstractServiceProvider;
use Omega\Database\Model\Model;
use Omega\Event\Dispatcher\Dispatcher;
use Omega\Event\Dispatcher\DispatcherInterface;

/**
 * Class EventServiceProvider.
 *
 * Registers the event dispatcher in the application container.
 *
 * @category   Omega
 * @package    Event
 * @link       https://omega-mvc.github.io
 * @author     Adriano Giovannini <agisoftt@gmail.com>
 * @copyright  Copyright (c) 2025 - 2026 Adriano Giovannini (https://omega-mvc.github.io)
 * @license    https://www.gnu.org/licenses/gpl-3.0-standalone.html     GPL V3.0+
 * @version    2.0.0
 */
class EventServiceProvider extends AbstractServiceProvider
{
    /**
     * Register event dispatcher services.
     *
     * @return void
     */
    public function boot(): void
    {
        $this->app->set(DispatcherInterface::class, static function (): DispatcherInterface {
            $dispatcher = new Dispatcher();
            Model::setEventDispatcher($dispatcher);

            return $dispatcher;
        });

        $this->app->set('events', static fn (): DispatcherInterface => new Dispatcher());
    }
}
