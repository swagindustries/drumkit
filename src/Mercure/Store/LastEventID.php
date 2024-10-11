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
