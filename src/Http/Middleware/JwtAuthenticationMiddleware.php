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

namespace SwagIndustries\MercureRouter\Http\Middleware;

use Amp\Http\Server\Middleware;
use Amp\Http\Server\Request;
use Amp\Http\Server\RequestHandler;
use Amp\Http\Server\Response;
use Amp\Http\Status;
use Amp\Promise;
use Amp\Success;
use SwagIndustries\MercureRouter\Exception\BearerNotFoundException;
use SwagIndustries\MercureRouter\Exception\WrongBearerException;
use SwagIndustries\MercureRouter\Security\Security;

abstract class JwtAuthenticationMiddleware implements Middleware
{
    public function __construct(protected Security $security)
    {
    }

    public function handleRequest(Request $request, RequestHandler $requestHandler): Promise
    {
        try {
            if (!$this->validate($request)) {
                return new Success($this->error('Invalid token'));
            }
        } catch (BearerNotFoundException $e) {
            return new Success($this->error('Missing authentication'));
        } catch (WrongBearerException $e) {
            return new Success($this->error('Wrong authentication'));
        }

        return $requestHandler->handleRequest($request);
    }

    protected abstract function validate(Request $request);

    private function error(string $error): Response
    {
        return new Response(Status::UNAUTHORIZED, [
            "content-type" => "application/json; charset=utf-8"
        ], json_encode(['message' => $error]));
    }
}
