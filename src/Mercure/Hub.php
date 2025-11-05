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

namespace SwagIndustries\MercureRouter\Mercure;

use Ds\Deque;
use SwagIndustries\MercureRouter\Mercure\Store\EventStoreInterface;
use SwagIndustries\MercureRouter\Mercure\Store\LastEventID;

class Hub
{
    public const MERCURE_PATH = '/.well-known/mercure';

    /** @var $subscriber<Subscriber> */
    private Deque $subscribers;
    /** @var Subscriber[] */
    private Privacy $privacy;

    public function __construct(private EventStoreInterface $store, ?Privacy $privacy = null)
    {
        $this->privacy = $privacy ?? new Privacy;
        $this->subscribers = new Deque();
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
     * @return Deque<Subscriber>
     */
    public function getSubscribers(): Deque
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
        $this->subscribers->push($subscriber);
        if ($subscriber->lastEventId !== null) {
            $this->reconciliate($subscriber, $subscriber->lastEventId);
        }
    }

    public function removeSubscriber(Subscriber $subscriberToRemove): void
    {
        foreach ($this->subscribers as $key => $subscriber) {
            if ($subscriber === $subscriberToRemove) {
                $this->subscribers->remove($key);
                break;
            }
        }
    }

    public function getLastEventID(): LastEventID
    {
        return $this->store->getLastEventID();
    }

    public function stop(): void
    {
        foreach($this->subscribers as $subscriber) {
            $subscriber->emitter->complete();
        }
    }

    private function reconciliate(Subscriber $subscriber, string $lastEventID)
    {
        $events = $this->store->reconcile($lastEventID);
        foreach($events as $event) {
            $subscriber->dispatch($event);
        }
    }
}
