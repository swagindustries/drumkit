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

namespace SwagIndustries\MercureRouter\RequestHandlers;

use SwagIndustries\MercureRouter\Controller\Dev\RedirectToDebuggerController;
use SwagIndustries\MercureRouter\Controller\Dev\RenderDebuggerController;
use SwagIndustries\MercureRouter\Controller\NotFoundController;
use SwagIndustries\MercureRouter\Controller\ProducerController;
use SwagIndustries\MercureRouter\Controller\SubscribeController;
use SwagIndustries\MercureRouter\Mercure\Hub;


class DevRequestHandlerRouterFactory implements RequestHandlerRouterFactoryInterface
{
    public function createRequestHandlerRouter(Hub $mercure): RequestHandlerRouter
    {
        return new RequestHandlerRouter([
            new RedirectToDebuggerController(),
            new RenderDebuggerController(),
            new ProducerController($mercure),
            new SubscribeController($mercure),
            new NotFoundController(),
        ]);
    }
}
