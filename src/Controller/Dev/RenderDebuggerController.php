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
use SwagIndustries\MercureRouter\Mercure\Hub;

class RenderDebuggerController implements RequestHandler
{
    public const URL = Hub::MERCURE_PATH . '/ui/';
    public function handleRequest(Request $request): Response
    {
        return new Response(
            HttpStatus::OK,
            ["content-type" => "text/html; charset=utf-8"],
            str_replace(
                '{{HOST}}',
                $request->getUri()->getHost(),
                file_get_contents(__DIR__ . '/../../../ui/index.html')
            )
        );
    }
}
