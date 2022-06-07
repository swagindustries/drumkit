<?php

namespace SwagIndustries\MercureRouter\Mercure;

use SwagIndustries\MercureRouter\SSE\Event;

class Update extends Event
{
    /** @var string[] */
    public readonly array $topics;

    public function __construct(array $topics, ?string $data, bool $private, string $id, ?string $type, ?int $retry = null)
    {
        parent::__construct($data, $private, $id, $type, $retry);
        $this->topics = $topics;
    }
}
