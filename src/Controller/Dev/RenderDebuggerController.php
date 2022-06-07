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
use Amp\Http\Server\Response;
use Amp\Http\Status;
use Amp\Promise;
use Amp\Success;
use SwagIndustries\MercureRouter\Controller\ControllerInterface;
use SwagIndustries\MercureRouter\Mercure\Hub;

class RenderDebuggerController implements ControllerInterface
{
    public const URL = Hub::MERCURE_PATH . '/ui/';
    public function support(Request $request): bool
    {
        return $request->getUri()->getPath() === self::URL && $request->getMethod() === 'GET';
    }

    public function resolve(Request $request): Promise
    {
        return new Success(new Response(
            Status::OK,
            ["content-type" => "text/html; charset=utf-8"],
            file_get_contents(__DIR__ . '/../../../ui/index.html')
        ));
    }
}
