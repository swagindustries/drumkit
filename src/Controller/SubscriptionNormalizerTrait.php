<?php

namespace SwagIndustries\MercureRouter\Controller;

use SwagIndustries\MercureRouter\Mercure\Hub;
use SwagIndustries\MercureRouter\Mercure\Subscriber;

trait SubscriptionNormalizerTrait
{
    private function normalizeSubscription(Subscriber $subscriber, string $topic, bool $active = true): array
    {
        return [
            'id' => Hub::MERCURE_PATH . '/subscriptions/'.urlencode($topic) . '/'.urlencode($subscriber->id),
            'type' => 'Subscription',
            'subscriber' => $subscriber->id,
            'topic' => $topic,
            'active' => $active,
            'payload' => $subscriber->payload
        ];
    }
}
