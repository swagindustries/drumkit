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

    /**
     * The reconnection time. If the connection to the server is lost,
     * the browser will wait for the specified time before attempting to
     * reconnect. This must be an integer, specifying the reconnection
     * time in milliseconds. If a non-integer value is specified, the
     * field is ignored.
     */
    public readonly ?int $retry;

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
