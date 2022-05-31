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

use Amp\Promise;
use Amp\Success;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use SwagIndustries\MercureRouter\Mercure\Update;

class InMemoryEventStore implements EventStoreInterface
{
    /** @var array<string, Update> id is the array key, the array is ordered */
    private array $store;
    private int $size;
    private LoggerInterface $logger;

    public function __construct($size = 1000, LoggerInterface $logger = null)
    {
        $this->store = [];
        $this->size = $size;
        $this->logger = $logger ?? new NullLogger();
    }

    public function store(Update $update): Promise
    {
        if (count($this->store) >= $this->size) {
            $removed = array_shift($this->store);
            $this->logger->debug('Remove update', ['id' => $removed->id]);
        }

        $this->logger->debug('Store new update', ['id' => $update->id]);
        $this->store[$update->id] = $update;

        return new Success();
    }

    public function reconcile(string $lastEventId): Promise
    {
        $sendEvents = $lastEventId === self::EARLIEST;

        $reconciliation = [];
        foreach ($this->store as $eventId => $event) {
            if ($lastEventId === $eventId) {
                $sendEvents = true;
            }

            if ($sendEvents) {
                $reconciliation[$eventId] = $event;
            }
        }

        return new Success($reconciliation);
    }
}
