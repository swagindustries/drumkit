<?php

namespace SwagIndustries\MercureRouter\Mercure\Store;

final class LastEventID
{
    public function __construct(private ?string $id = null)
    {

    }

    public function __toString()
    {
        if (null === $this->id) {
            return EventStoreInterface::EARLIEST;
        }

        return $this->id;
    }
}
