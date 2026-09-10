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

namespace Omega\Event\Listeners;

use Omega\Cache\CacheManager;
use Omega\Event\EventInterface;
use Omega\Event\SubscriberInterface;

/**
 * Listener that clears cache when model data changes.
 *
 * This listener can be registered to handle model.saved and model.deleted events,
 * automatically clearing relevant cache entries.
 *
 * @category  Omega
 * @package   Event
 * @subpackage Listeners
 * @link      https://omega-mvc.github.io
 * @author    Adriano Giovannini <agisoftt@gmail.com>
 * @copyright Copyright (c) 2025 - 2026 Adriano Giovannini (https://omega-mvc.github.io)
 * @license   https://www.gnu.org/licenses/gpl-3.0-standalone.html     GPL V3.0+
 * @version   2.0.0
 */
class CacheClearListener implements SubscriberInterface
{
    /**
     * @var CacheManager|null The cache manager instance.
     */
    private ?CacheManager $cache = null;

    /**
     * Set the cache manager instance.
     *
     * @param CacheManager $cache The cache manager to use.
     * @return void
     */
    public function setCacheManager(CacheManager $cache): void
    {
        $this->cache = $cache;
    }

    /**
     * Returns an array of event names this subscriber wants to listen to.
     *
     * @return array<string, array{0: string, 1?: int}> Event names to listen for.
     */
    public static function getSubscribedEvents(): array
    {
        return [
            'model.saved'   => ['onModelSaved', 0],
            'model.deleted' => ['onModelDeleted', 0],
        ];
    }

    /**
     * Handle the model.saved event.
     *
     * @param EventInterface $event The model event.
     * @return void
     */
    public function onModelSaved(EventInterface $event): void
    {
        $this->clearTableCache($event);
    }

    /**
     * Handle the model.deleted event.
     *
     * @param EventInterface $event The model event.
     * @return void
     */
    public function onModelDeleted(EventInterface $event): void
    {
        $this->clearTableCache($event);
    }

    /**
     * Clear cache entries for the affected table.
     *
     * @param EventInterface $event The model event.
     * @return void
     */
    private function clearTableCache(EventInterface $event): void
    {
        if ($this->cache === null) {
            return;
        }

        $table = $event->getArgument('table', '');
        if ($table !== '') {
            $this->cache->clear("model.{$table}");
        }
    }
}
