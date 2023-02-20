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

use Amp\Http\HttpStatus;
use Amp\Http\Server\Request;
use Amp\Http\Server\RequestHandler;
use Amp\Http\Server\Response;

class RedirectToDebuggerController implements RequestHandler
{
    public function handleRequest(Request $request): Response
    {
        return new Response(HttpStatus::TEMPORARY_REDIRECT, [
            'location' => RenderDebuggerController::URL
        ]);
    }
}
