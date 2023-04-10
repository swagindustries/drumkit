<?php

namespace SwagIndustries\MercureRouter\Test\Configuration;

use Amp\Http\Server\HttpServer;
use Amp\Http\Server\Router;
use Amp\Http\Server\SocketHttpServer;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use SwagIndustries\MercureRouter\Configuration\Options;
use SwagIndustries\MercureRouter\Configuration\SecurityOptions;
use SwagIndustries\MercureRouter\Exception\WrongOptionException;
use SwagIndustries\MercureRouter\Http\Router\RouterFactory;
use SwagIndustries\MercureRouter\Mercure\Hub;
use SwagIndustries\MercureRouter\RequestHandlers\RequestHandlerRouterFactoryInterface;

class OptionsTest extends TestCase
{
    use ProphecyTrait;
    public function testItThrowsErrorIfNoCertificateProvided()
    {
        $this->expectException(WrongOptionException::class);

        new Options('/tmp/foo', '/tmp/bar');
    }

    public function testItBuildsARequestHandlerRouter()
    {
        $httpServer = $this->prophesize(HttpServer::class)->reveal();
        $router = $this->prophesize(Router::class)->reveal();
        $routerFactory = $this->prophesize(RouterFactory::class);
        $routerFactory->createRouter($httpServer, Argument::cetera())->willReturn($router);
        $options = new Options(
            __DIR__ .'/../../ssl/mercure-router.local.pem',
            __DIR__ .'/../../ssl/mercure-router.local-key.pem',
            requestHandlerRouterFactory: $routerFactory->reveal(),
            subscriberSecurity: $this->prophesize(SecurityOptions::class)->reveal(),
            publisherSecurity: $this->prophesize(SecurityOptions::class)->reveal(),
        );

        $this->assertEquals($router, $options->requestHandlerRouter($httpServer));
    }
}
