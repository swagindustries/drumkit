<?php

namespace SwagIndustries\MercureRouter;

use SwagIndustries\MercureRouter\Configuration\Options;

class MercureServerFactory
{
    public function create(Options $options): Server
    {
        return new Server($options);
    }
}
