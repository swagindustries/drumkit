<?php

namespace SwagIndustries\MercureRouter\Http\Middleware;

use Amp\Http\Server\Request;

final class SubscribeJwtAuthenticationMiddleware extends JwtAuthenticationMiddleware
{
    protected function validate(Request $request)
    {
        return $this->security->validateSubscribeRequest($request);
    }
}

