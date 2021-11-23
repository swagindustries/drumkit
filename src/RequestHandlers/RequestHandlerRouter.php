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

use Amp\Http\Server\Request;
use Amp\Http\Server\RequestHandler;
use Amp\Promise;
use SwagIndustries\MercureRouter\Controller\ControllerInterface;
use SwagIndustries\MercureRouter\Exception\NoControllerFoundException;
use Webmozart\Assert\Assert;
use function Amp\call;

class RequestHandlerRouter implements RequestHandler
{
    /** @var ControllerInterface[] */
    private array $controllers;

    public function __construct(array $controllers)
    {
        Assert::allIsInstanceOf($controllers, ControllerInterface::class);
        $this->controllers = $controllers;
    }

    public function handleRequest(Request $request): Promise
    {
        foreach ($this->controllers as $controller) {
            if ($controller->support($request)) {
                return call([$controller, 'resolve'], $request);
            }
        }

        throw new NoControllerFoundException('Impossible to find a controller supporting the current request');
    }
}
