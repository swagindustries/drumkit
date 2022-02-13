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

namespace SwagIndustries\MercureRouter\Controller;

use Amp\Http\Server\Request;
use Amp\Http\Server\Response;
use Amp\Http\Status;
use Amp\Promise;
use SwagIndustries\MercureRouter\Mercure\MimeTypeFileExtensionResolver;
use function Amp\call;

final class NotFoundController implements ControllerInterface
{
    public function support(Request $request): bool
    {
        return true;
    }

    public function resolve(Request $request): Promise
    {
        return call(function () {
            return new Response(
                Status::NOT_FOUND,
                ["content-type" => "text/plain; charset=utf-8"],
                '404 Not found'
            );
        });
    }
}
