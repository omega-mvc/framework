<?php

declare(strict_types=1);

namespace Tests\Event\Support;

use Omega\Event\EventInterface;

/**
 * Container service acting as an invokable event listener.
 */
class InvokableService
{
    /** @var list<EventInterface> Recorded dispatched events. */
    private array $calls = [];

    /**
     * Records the dispatched event.
     *
     * @param EventInterface $event The dispatched event.
     * @return void
     */
    public function __invoke(EventInterface $event): void
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