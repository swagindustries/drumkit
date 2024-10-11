<?php
/**
 * This file is a part of mercure-router-php package.
 *
 * (c) Swag Industries <nek.dev@gmail.com>
 *
 * For the full license, take a look to the LICENSE file
 * on the root directory of this project
 */

declare(strict_types=1);

namespace SwagIndustries\MercureRouter\Controller\Subscription;

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
