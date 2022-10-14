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

use Amp\Promise;
use SwagIndustries\MercureRouter\Mercure\Store\EventStoreInterface;
use function Amp\call;

class Hub
{
    public const MERCURE_PATH = '/.well-known/mercure';

    /** @var Subscriber[] */
    private array $subscribers; // @todo: perf opti: using another thing than php array
    private Privacy $privacy;

    public function __construct(private EventStoreInterface $store, Privacy $privacy = null)
    {
        $this->privacy = $privacy ?? new Privacy;
        $this->subscribers = [];
    }

    public function publish(Update $update): Promise
    {
        return call(function () use ($update) {
            foreach ($this->subscribers as $subscriber) {
                yield $this->store->store($update);
                if ($this->privacy->subscriberCanReceive($subscriber, $update)) {
                    yield $subscriber->dispatch($update);
                }
            }
        });
    }

    public function addSubscriber($subscriber): void
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
}
