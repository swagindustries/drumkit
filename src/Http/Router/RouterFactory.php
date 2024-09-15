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

use Amp\Http\Server\DefaultErrorHandler;
use Amp\Http\Server\HttpServer;
use Amp\Http\Server\Router;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use SwagIndustries\MercureRouter\Controller\NotFoundController;
use SwagIndustries\MercureRouter\Controller\PublishController;
use SwagIndustries\MercureRouter\Controller\ResponseMode;
use SwagIndustries\MercureRouter\Controller\SubscribeController;
use SwagIndustries\MercureRouter\Controller\GetSubscriptionsController;
use SwagIndustries\MercureRouter\Http\Middleware\PublishJwtAuthenticationMiddleware;
use SwagIndustries\MercureRouter\Http\Middleware\SubscribeJwtAuthenticationMiddleware;
use SwagIndustries\MercureRouter\Mercure\Hub;
use SwagIndustries\MercureRouter\Security\Security;
use function Amp\Http\Server\Middleware\stackMiddleware;

class RouterFactory
{
    protected $verbose = false;

    public function __construct(private bool $activeSubscription = false, private LoggerInterface $logger = new NullLogger()) {}

    public function createRouter(HttpServer $httpServer, Hub $mercure, Security $security): Router
    {
        $router = new Router($httpServer, $this->logger, new DefaultErrorHandler());
        $notFoundController = new NotFoundController();
        $router->setFallback($notFoundController);

        if ($this->activeSubscription) {
            // TODO: add security (subscriptions are private)
            // To access to the URLs exposed by the web API, clients MUST be authorized according to the rules defined in
            // authorization. The requested URL MUST match at least one of the topic selectors provided in the
            // `mercure.subscribe` key of the JWS.
            $router->addRoute('GET', Hub::MERCURE_PATH . '/subscriptions/{topic}/{subscriber}', stackMiddleware(
                new GetSubscriptionsController($mercure, $notFoundController)
            ));
            $router->addRoute('GET', Hub::MERCURE_PATH . '/subscriptions/{topic}', stackMiddleware(
                new GetSubscriptionsController($mercure, $notFoundController)
            ));
            $router->addRoute('GET', Hub::MERCURE_PATH . '/subscriptions', stackMiddleware(
                new GetSubscriptionsController($mercure, $notFoundController)
            ));
        }

        $router->addRoute('POST', Hub::MERCURE_PATH, stackMiddleware(
            new PublishController(
                $mercure,
                $this->verbose ? ResponseMode::VERBOSE : ResponseMode::NORMAL,
                $this->logger,
            ),
            new PublishJwtAuthenticationMiddleware($security)
        ));

        $router->addRoute('GET', Hub::MERCURE_PATH, stackMiddleware(
            new SubscribeController(
                $mercure,
                $this->logger,
            ),
            new SubscribeJwtAuthenticationMiddleware($security)
        ));

        return $router;
    }
}
