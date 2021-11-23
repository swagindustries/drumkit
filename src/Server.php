<?php
/**
 * This file is a part of mercure-router-php package.
 *
 * (c) Swag Industries <nek.dev@gmail.com>
 *
 * For the full license, take a look to the LICENSE file
 * on the root directory of this project
 */

namespace SwagIndustries\MercureRouter;

use Amp\Http\Server\HttpServer;
use Amp\Http\Server\Middleware\CompressionMiddleware;
use Amp\Loop;
use Amp\Socket\BindContext;
use Amp\Socket\Certificate;
use Amp\Socket\ServerTlsContext;
use Amp\Socket\Server as SocketServer;
use SwagIndustries\MercureRouter\Configuration\Options;
use function Amp\Http\Server\Middleware\stack;

class Server
{
    public function __construct(private Options $options) {}

    public function start()
    {
        Loop::run(function () {
            $certificate = new Certificate($this->options->certificate(), $this->options->key());
            $logger = $this->options->logger();

            $tlsContext = (new BindContext())
                ->withTlsContext((new ServerTlsContext())->withDefaultCertificate($certificate));

            if ($this->options->unsecuredPort() === 80 && 'root' !== $currentUser = get_current_user()) {
                $logger->warning("You ran this server with user $currentUser on the port 80 (which is the default configuration), this port cannot be bind in another user than root. Please run this server as root or change the used ports. (See Options)");
            }

            $connections = iterator_to_array($this->generateConnections($this->options, $tlsContext));

            $httpServer = new HttpServer($connections, stack(
                $this->options->requestHandlerRouter(),

                // Enabling compression
                // Those values are required to be changed for SSE because the compression middleware will buffer a lot
                // will we need to stream the response
                // See https://github.com/amphp/http-server/issues/324 for more information
                new CompressionMiddleware(
                    minimumLength: 12,
                    chunkSize: 1,
                )
            ), $logger);

            yield $httpServer->start();
        });

    }

    public function generateConnections(Options $options, BindContext $tlsContext): \Generator
    {
        foreach ($options->hosts() as $host) {
            yield SocketServer::listen($host . ':' . $options->unsecuredPort());
            yield SocketServer::listen($host . ':' . $options->tlsPort(), $tlsContext);
        }
    }
}
