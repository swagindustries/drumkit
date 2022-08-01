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

namespace SwagIndustries\MercureRouter\Controller\Dev;

use Amp\Http\Server\Request;
use Amp\Http\Server\RequestHandler;
use Amp\Http\Server\Response;
use Amp\Http\Status;
use Amp\Promise;
use Amp\Success;
use SwagIndustries\MercureRouter\Controller\ControllerInterface;
use SwagIndustries\MercureRouter\Mercure\Hub;

class RedirectToDebuggerController implements RequestHandler
{
    public function handleRequest(Request $request): Promise
    {
        return new Success(new Response(Status::TEMPORARY_REDIRECT, [
            'location' => RenderDebuggerController::URL
        ]));
    }
}
