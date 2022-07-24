<?php

namespace SwagIndustries\MercureRouter\Security;

use Amp\Http\Server\Middleware;
use Amp\Http\Server\Request;
use Amp\Http\Server\RequestHandler;
use Amp\Http\Server\Response;
use Amp\Http\Status;
use Amp\Promise;
use Amp\Success;
use SwagIndustries\MercureRouter\Security\Extractor\ChainExtractor;

class JwtAuthenticationMiddleware implements Middleware
{
    public function __construct(private ChainExtractor $extractor, private TokenValidator $validator)
    {
    }

    public function handleRequest(Request $request, RequestHandler $requestHandler): Promise
    {
        $bearer = $this->extractor->extract($request);
        if (null === $bearer) {
            return new Success($this->error('Missing authentication'));
        }

        if (!$this->validator->validate($bearer)) {
            return new Success($this->error('Wrong token'));
        }

        return $requestHandler->handleRequest($request);
    }

    private function error(string $error): Response
    {
        return new Response(Status::UNAUTHORIZED, [
            "content-type" => "application/json; charset=utf-8"
        ], json_encode(['message' => $error]));
    }
}
