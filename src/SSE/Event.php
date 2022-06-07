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
namespace SwagIndustries\MercureRouter\SSE;

class Event
{
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

    public function __construct(?string $data, bool $private, string $id, ?string $type, ?int $retry = null)
    {
        $this->data = $data;
        $this->private = $private;
        $this->id = $id;
        $this->type = $type;
        $this->retry = $retry;
    }

    public function format(): string
    {
        // URL Encode ??
        $data = $this->data;

        return "event: {$this->type}\ndata: $data\n\n";
    }
}
