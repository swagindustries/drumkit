<?php

namespace SwagIndustries\MercureRouter\Mercure;

class Update
{
    /** @var string[] */
    public readonly array $topics;
    public readonly ?string $data;
    public readonly bool $private;
    public readonly string $id;
    public readonly ?string $type;
    public readonly int $retry;

    public function __construct(array $topics, ?string $data, bool $private, string $id, ?string $type, ?int $retry = null)
    {
        $this->topics = $topics;
        $this->data = $data;
        $this->private = $private;
        $this->id = $id;
        $this->type = $type;
        $this->retry = $retry;
    }
}
