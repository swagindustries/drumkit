<?php
/**
 * This file is a part of mercure-router-php package.
 *
 * (c) Swag Industries <nek.dev@gmail.com>
 *
 * For the full license, take a look to the LICENSE file
 * on the root directory of this project
 */

namespace SwagIndustries\MercureRouter\RequestHandlers;

use SwagIndustries\MercureRouter\Controller\NotFoundController;

class RequestHandlerRouterFactory implements RequestHandlerRouterFactoryInterface
{
    public function createRequestHandlerRouter(): RequestHandlerRouter
    {
        return new RequestHandlerRouter([
            new NotFoundController()
        ]);
    }
}
