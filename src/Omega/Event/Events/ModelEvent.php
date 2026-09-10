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

namespace Omega\Event\Events;

use Omega\Database\Model\Model;
use Omega\Event\Event;

/**
 * Event dispatched during model lifecycle operations.
 *
 * @category  Omega
 * @package   Event
 * @subpackage Events
 * @link      https://omega-mvc.github.io
 * @author    Adriano Giovannini <agisoftt@gmail.com>
 * @copyright Copyright (c) 2025 - 2026 Adriano Giovannini (https://omega-mvc.github.io)
 * @license   https://www.gnu.org/licenses/gpl-3.0-standalone.html     GPL V3.0+
 * @version   2.0.0
 */
class ModelEvent extends Event
{
    /**
     * Create a model.created event.
     *
     * @param Model $model The created model.
     * @return static
     */
    public static function created(Model $model): static
    {
        $event = new static('model.created');
        $event->setArgument('model', $model);
        $event->setArgument('table', $model->tableName ?? '');

        return $event;
    }

    /**
     * Create a model.saved event.
     *
     * @param Model $model  The saved model.
     * @param bool  $created Whether this was a create (true) or update (false).
     * @return static
     */
    public static function saved(Model $model, bool $created = false): static
    {
        $event = new static('model.saved');
        $event->setArgument('model', $model);
        $event->setArgument('table', $model->tableName ?? '');
        $event->setArgument('created', $created);

        return $event;
    }

    /**
     * Create a model.deleted event.
     *
     * @param Model $model The deleted model.
     * @return static
     */
    public static function deleted(Model $model): static
    {
        $event = new static('model.deleted');
        $event->setArgument('model', $model);
        $event->setArgument('table', $model->tableName ?? '');

        return $event;
    }
}
