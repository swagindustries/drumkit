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

namespace SwagIndustries\MercureRouter\Mercure\Store;

use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use SwagIndustries\MercureRouter\Mercure\Update;
use Ds\Map;

class InMemoryEventStore implements EventStoreInterface
{
    /** @var Map<string, Update> id is the array key, the array is ordered */
    private Map $store;
    private int $size;
    private LoggerInterface $logger;

    public function __construct($size = 1000, LoggerInterface $logger = null)
    {
        $this->store = new Map();
        $this->size = $size;
        $this->logger = $logger ?? new NullLogger();
    }

    public function store(Update $update): void
    {
        if ($this->store->count() >= $this->size) {
            $toBeRemoved = $this->store->first();
            $this->store->remove($toBeRemoved->key);
            $this->logger->debug('Remove update', ['id' => $toBeRemoved->value->id]);
        }

        $this->logger->debug('Store new update', ['id' => $update->id]);
        $this->store->put($update->id, $update);
    }

    public function reconcile(string $lastEventId): array
    {
        $sendEvents = $lastEventId === self::EARLIEST;

        $reconciliation = [];
        foreach ($this->store->keys() as $eventId) {
            if ($lastEventId === $eventId) {
                $sendEvents = true;
            }

            if ($sendEvents) {
                $event = $this->store->get($eventId);
                $reconciliation[] = $event;
            }
        }

        return $reconciliation;
    }

    public function getLastEventID(): LastEventID
    {
        if ($this->store->isEmpty()) {
            return new LastEventID();
        }

        /** @var Update $event */
        $event = $this->store->last()->value;

        return new LastEventID($event->id);
    }
}
