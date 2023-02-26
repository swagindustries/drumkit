<?php
/**
 * This file is a part of mercure-router-php package.
 *
 * (c) Swag Industries <nek.dev@gmail.com>
 *
 * For the full license, take a look to the LICENSE file
 * on the root directory of this project
 */

namespace SwagIndustries\MercureRouter\Mercure;

use SwagIndustries\MercureRouter\Mercure\Store\EventStoreInterface;
use SwagIndustries\MercureRouter\Mercure\Store\LastEventID;

class Hub
{
    public const MERCURE_PATH = '/.well-known/mercure';

    /** @var Subscriber[] */
    private array $subscribers; // @todo: perf opti: using another thing than php array
    /** @var Subscriber[] */
    private Privacy $privacy;

    public function __construct(private EventStoreInterface $store, Privacy $privacy = null)
    {
        $this->privacy = $privacy ?? new Privacy;
        $this->subscribers = [];
    }

    public function publish(Update $update)
    {
        $this->store->store($update);
        foreach ($this->subscribers as $subscriber) {
            if ($this->privacy->subscriberCanReceive($subscriber, $update)) {
                $subscriber->dispatch($update);
            }
        }
    }

    /**
     * @return Subscriber[]
     */
    public function getSubscribers(): array
    {
        return $this->subscribers;
    }

    public function getSubscriber(string $id): Subscriber|null
    {
        foreach ($this->subscribers as $subscriber) {
            if ($subscriber->id === $id) {
                return $subscriber;
            }
        }

        return null;
    }

    public function addSubscriber(Subscriber $subscriber): void
    {
        $this->subscribers[] = $subscriber;
    }

    public function removeSubscriber(Subscriber $subscriberToRemove): void
    {
        foreach ($this->subscribers as $key => $subscriber) {
            if ($subscriber === $subscriberToRemove) {
                unset($this->subscribers[$key]);
                break;
            }
        }
    }

    public function getLastEventID(): LastEventID
    {
        return $this->store->getLastEventID();
    }
}
