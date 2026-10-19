<?php

declare(strict_types=1);

namespace Tests\Event\Support;

use Omega\Event\EventInterface;

/**
 * Container service exposing a named method used by a service event listener.
 */
class MethodService
{
    /** @var list<EventInterface> Recorded dispatched events. */
    private array $calls = [];

    /**
     * Records the dispatched event.
     *
     * @param EventInterface $event The dispatched event.
     * @return void
     */
    public function handle(EventInterface $event): void
    {
        $this->calls[] = $event;
    }

    /**
     * Returns the recorded dispatched events.
     *
     * @return list<EventInterface> The dispatched events.
     */
    public function getCalls(): array
    {
        return $this->calls;
    }
}