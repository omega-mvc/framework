<?php

declare(strict_types=1);

namespace Tests\Event\Support;

use Omega\Event\SubscriberInterface;

/**
 * Subscriber with entries that must be skipped during registration.
 */
class TestSkippingSubscriber implements SubscriberInterface
{
    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents(): array
    {
        return [
            'subscriber.empty'         => '',
            'subscriber.emptyArray'    => [''],
            'subscriber.missing'       => ['missingMethod'],
            'subscriber.missingString' => 'missingMethod',
        ];
    }
}