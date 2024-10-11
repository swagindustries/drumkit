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

namespace SwagIndustries\MercureRouter;

use SwagIndustries\MercureRouter\Configuration\Options;

class MercureServerFactory
{
    public function create(Options $options): Server
    {
        return new Server($options);
    }
}
