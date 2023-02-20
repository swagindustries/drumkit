<?php

/**
 * This file is a part of mercure-router-php package.
 *
 * (c) Swag Industries <nek.dev@gmail.com>
 *
 * For the full license, take a look to the LICENSE file
 * on the root directory of this project
 */

namespace SwagIndustries\MercureRouter\Mercure\Store;

use SwagIndustries\MercureRouter\Mercure\Update;

interface EventStoreInterface
{
    public const EARLIEST = 'earliest';

    public function store(Update $update);
    public function reconcile(string $lastEventId);

    public function getLastEventID(): LastEventID;
}
