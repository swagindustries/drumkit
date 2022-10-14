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

use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use SwagIndustries\MercureRouter\Controller\NotFoundController;
use SwagIndustries\MercureRouter\Controller\PublishController;
use SwagIndustries\MercureRouter\Controller\ResponseMode;
use SwagIndustries\MercureRouter\Controller\SubscribeController;
use SwagIndustries\MercureRouter\Mercure\Hub;

class RequestHandlerRouterFactory implements RequestHandlerRouterFactoryInterface
{
    public function __construct(private LoggerInterface $logger = new NullLogger()) {}

    public function createRequestHandlerRouter(Hub $mercure): RequestHandlerRouter
    {
        return new RequestHandlerRouter([
            new PublishController($mercure, ResponseMode::NORMAL, $this->logger),
            new SubscribeController($mercure, $this->logger),
            new NotFoundController(),
        ]);
    }
}
