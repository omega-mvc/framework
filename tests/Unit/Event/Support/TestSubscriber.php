<?php

declare(strict_types=1);

namespace Tests\Event\Support;

use Omega\Event\EventInterface;
use Omega\Event\Priority;
use Omega\Event\SubscriberInterface;

/**
 * Subscriber with mixed registration formats, including an event that stops propagation.
 */
class TestSubscriber implements SubscriberInterface
{
    /** @var list<string> Recorded listener invocations. */
    private array $calls = [];

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents(): array
    {
        return [
            'subscriber.alpha' => 'onAlpha',
            'subscriber.beta'  => ['onBeta', Priority::HIGH],
            'subscriber.gamma' => ['onGamma', -1],
            'subscriber.stop'  => 'onStop',
        ];
    }

    /**
     * Handles the subscriber.alpha event.
     *
     * @param EventInterface $event The dispatched event.
     * @return void
     */
    public function onAlpha(EventInterface $event): void
    {
        $this->calls[] = 'alpha';
    }

    /**
     * Handles the subscriber.beta event.
     *
     * @param EventInterface $event The dispatched event.
     * @return void
     */
    public function onBeta(EventInterface $event): void
    {
        $this->calls[] = 'beta';
    }

    /**
     * Handles the subscriber.gamma event.
     *
     * @param EventInterface $event The dispatched event.
     * @return void
     */
    public function onGamma(EventInterface $event): void
    {
        $this->calls[] = 'gamma';
    }

    /**
     * Handles the subscriber.stop event and stops propagation.
     *
     * @param EventInterface $event The dispatched event.
     * @return void
     */
    public function onStop(EventInterface $event): void
    {
        $this->calls[] = 'stop';
        $event->stopPropagation();
    }

    /**
     * Returns the recorded listener invocations.
     *
     * @return list<string> The recorded invocations.
     */
    public function getCalls(): array
    {
        return $this->calls;
    }
}