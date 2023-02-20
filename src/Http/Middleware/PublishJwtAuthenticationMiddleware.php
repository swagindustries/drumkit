<?php

namespace SwagIndustries\MercureRouter\Http\Middleware;

use Amp\Http\Server\Request;

final class PublishJwtAuthenticationMiddleware extends JwtAuthenticationMiddleware
{
    protected function validate(Request $request)
    {
        return $this->security->validatePublishRequest($request);
    }
}
