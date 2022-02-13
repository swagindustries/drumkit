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

class Hub
{
    /** @var Subscriber[] */
    private array $subscribers;
    private Privacy $privacy;

    public function __construct(Privacy $privacy = null)
    {
        $this->privacy = $privacy ?? new Privacy;
    }

    public function publish(Update $update): void
    {
        foreach ($this->subscribers as $subscriber) {
            if ($this->privacy->subscriberCanReceive($subscriber, $update)) {
                $subscriber->dispatch($update);
            }
        }
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
