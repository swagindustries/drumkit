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
use SwagIndustries\MercureRouter\Controller\Dev\RedirectToDebuggerController;
use SwagIndustries\MercureRouter\Controller\Dev\RenderDebuggerController;
use SwagIndustries\MercureRouter\Mercure\Hub;
use SwagIndustries\MercureRouter\Security\Security;

final class DevRouterFactory extends RouterFactory
{
    public function createRouter(Hub $mercure, Security $security): Router
    {
        $router = parent::createRouter($mercure, $security);

        $router->addRoute('GET', '/', new RedirectToDebuggerController());
        $router->addRoute('GET', RenderDebuggerController::URL, new RenderDebuggerController());

        return $router;
    }
}
