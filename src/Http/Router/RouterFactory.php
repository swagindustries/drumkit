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

namespace SwagIndustries\MercureRouter\Http\Router;

use Amp\Http\Server\Router;
use SwagIndustries\MercureRouter\Controller\NotFoundController;
use SwagIndustries\MercureRouter\Controller\PublishController;
use SwagIndustries\MercureRouter\Controller\SubscribeController;
use SwagIndustries\MercureRouter\Http\Middleware\PublishJwtAuthenticationMiddleware;
use SwagIndustries\MercureRouter\Http\Middleware\SubscribeJwtAuthenticationMiddleware;
use SwagIndustries\MercureRouter\Mercure\Hub;
use SwagIndustries\MercureRouter\Security\Security;
use function Amp\Http\Server\Middleware\stack;

class RouterFactory
{
    public function createRouter(Hub $mercure, Security $security): Router
    {
        $router = new Router();
        $router->setFallback(new NotFoundController());

        $router->addRoute('POST', Hub::MERCURE_PATH, stack(
            new PublishController($mercure),
            new PublishJwtAuthenticationMiddleware($security)
        ));

        $router->addRoute('GET', Hub::MERCURE_PATH, stack(
            new SubscribeController($mercure),
            new SubscribeJwtAuthenticationMiddleware($security)
        ));

        return $router;
    }
}
