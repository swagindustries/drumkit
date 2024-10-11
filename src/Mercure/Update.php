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
