<?php

namespace SwagIndustries\MercureRouter\Mercure;

class Update
{
    /** @var string[] */
    private array $topics;
    private ?string $data;
    private ?bool $private;
    private ?string $id;
    private ?string $type;
    private bool $retry; // Note: maybe int ?? see \Symfony\Component\Mercure\Update::__construct

    /**
     * @param string[] $topics
     * @param string|null $data
     * @param bool|null $private
     * @param string|null $id
     * @param string|null $type
     * @param bool $retry
     */
    public function __construct(array $topics, ?string $data = null, ?bool $private = null, ?string $id = null, ?string $type = null, bool $retry = false)
    {
        $this->topics = $topics;
        $this->data = $data;
        $this->private = $private;
        $this->id = $id;
        $this->type = $type;
        $this->retry = $retry;
    }


}
