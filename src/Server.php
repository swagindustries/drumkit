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

use Amp\Http\Server\DefaultErrorHandler;
use Amp\Http\Server\Driver\DefaultHttpDriverFactory;
use Amp\Http\Server\Middleware\CompressionMiddleware;
use Amp\Http\Server\SocketHttpServer;
use Amp\Socket\BindContext;
use SwagIndustries\MercureRouter\Configuration\Options;
use function Amp\Http\Server\Middleware\stack;
use Amp\Socket;
use function Amp\trapSignal;

class Server
{
    public function __construct(private Options $options) {}

    public function start()
    {
        $logger = $this->options->logger();

        if ($this->options->isDevMode()) {
            $logger->info('Running in dev mode...');
        }

        $certificate = new Socket\Certificate($this->options->certificate(), $this->options->key());

        $tlsContext = (new Socket\BindContext)
            ->withTlsContext((new Socket\ServerTlsContext)->withDefaultCertificate($certificate));

        $this->verifiyUserRights($this->options);

        $httpDriverFactory = new DefaultHttpDriverFactory($logger, streamTimeout: $this->options->streamTimeout());
        $httpServer = new SocketHttpServer(
            $logger,
            httpDriverFactory: $httpDriverFactory,
            // This will disable automatic compression configuration
            // we enable this by hand to have a better control
            enableCompression: false
        );

        $this->enableConnection($httpServer, $tlsContext);

        $httpServer->start(stack(
            $this->options->requestHandlerRouter($httpServer),
            new CompressionMiddleware(minimumLength: 12)
        ), new DefaultErrorHandler());


        // Await SIGINT or SIGTERM to be received.
        $signal = trapSignal([\SIGINT, \SIGTERM]);
        $logger->info(sprintf("Received signal %d, stopping HTTP server", $signal));

        // Todo: complete all subscriptions

        $httpServer->stop();
    }

    private function enableConnection(SocketHttpServer $server, BindContext $tlsContext): void
    {
        foreach ($this->options->hosts() as $host) {
            $server->expose($host . ':' . $this->options->unsecuredPort());
            $server->expose($host . ':' . $this->options->tlsPort(), $tlsContext);
        }
    }

    private function verifiyUserRights(Options $options)
    {
        if (!function_exists('posix_getuid')) {
            return; // This is for Windows
        }

        if ($this->options->unsecuredPort() === 80 && 0 !== posix_getuid()) {
            $currentUser = get_current_user();
            $options->logger()->warning("You ran this server with user $currentUser on the port 80 (which is the default configuration), this port cannot be bind in another user than root. Please run this server as root or change the used ports. (See Options)");
        }
    }
}
