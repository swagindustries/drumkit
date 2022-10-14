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
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use SwagIndustries\MercureRouter\Controller\NotFoundController;
use SwagIndustries\MercureRouter\Controller\PublishController;
use SwagIndustries\MercureRouter\Controller\ResponseMode;
use SwagIndustries\MercureRouter\Controller\SubscribeController;
use SwagIndustries\MercureRouter\Http\Middleware\PublishJwtAuthenticationMiddleware;
use SwagIndustries\MercureRouter\Http\Middleware\SubscribeJwtAuthenticationMiddleware;
use SwagIndustries\MercureRouter\Mercure\Hub;
use SwagIndustries\MercureRouter\Security\Security;
use function Amp\Http\Server\Middleware\stack;

class RouterFactory
{
    protected $verbose = false;

    public function __construct(private LoggerInterface $logger = new NullLogger()) {}

    public function createRouter(Hub $mercure, Security $security): Router
    {
        $router = new Router();
        $router->setFallback(new NotFoundController());

        $router->addRoute('POST', Hub::MERCURE_PATH, stack(
            new PublishController(
                $mercure,
                $this->verbose ? ResponseMode::VERBOSE : ResponseMode::NORMAL,
                $this->logger,
            ),
            new PublishJwtAuthenticationMiddleware($security)
        ));

        $router->addRoute('GET', Hub::MERCURE_PATH, stack(
            new SubscribeController(
                $mercure,
                $this->logger,
            ),
            new SubscribeJwtAuthenticationMiddleware($security)
        ));

        return $router;
    }
}
