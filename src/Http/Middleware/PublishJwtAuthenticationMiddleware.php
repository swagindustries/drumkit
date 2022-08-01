<?php

namespace SwagIndustries\MercureRouter\Http\Middleware;

use Amp\Http\Server\Middleware;
use Amp\Http\Server\Request;
use Amp\Http\Server\RequestHandler;
use Amp\Http\Server\Response;
use Amp\Http\Status;
use Amp\Promise;
use Amp\Success;
use SwagIndustries\MercureRouter\Security\Extractor\ChainExtractor;

final class PublishJwtAuthenticationMiddleware extends JwtAuthenticationMiddleware
{
    protected function validate(Request $request)
    {
        return $this->security->validatePublishRequest($request);
    }
}
