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
use Amp\Http\Server\Driver\ConnectionLimitingClientFactory;
use Amp\Http\Server\Driver\ConnectionLimitingServerSocketFactory;
use Amp\Http\Server\Driver\DefaultHttpDriverFactory;
use Amp\Http\Server\Driver\HttpDriverFactory;
use Amp\Http\Server\Driver\SocketClientFactory;
use Amp\Http\Server\Middleware\AllowedMethodsMiddleware;
use Amp\Http\Server\Middleware\CompressionMiddleware;
use Amp\Http\Server\Middleware\ConcurrencyLimitingMiddleware;
use Amp\Http\Server\SocketHttpServer;
use Amp\Socket\BindContext;
use Amp\Sync\LocalSemaphore;
use Cspray\Labrador\Http\Cors\ConfigurationBuilder;
use Cspray\Labrador\Http\Cors\CorsMiddleware;
use Cspray\Labrador\Http\Cors\SimpleConfigurationLoader;
use Psr\Log\LoggerInterface as PsrLogger;
use SwagIndustries\MercureRouter\Configuration\Options;
use function Amp\Http\Server\Middleware\stackMiddleware;
use Amp\Socket;
use function Amp\trapSignal;

class Server
{
    private const DEFAULT_CONCURRENCY_LIMIT = 1000;
    private const DEFAULT_CONNECTION_LIMIT = 1000;
    private const DEFAULT_CONNECTIONS_PER_IP_LIMIT = 10;

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
        $httpServer = self::createSocketHttpServer(
            $logger,
            httpDriverFactory: $httpDriverFactory,
        );

        $this->enableConnection($httpServer, $tlsContext);

        $corsLoader = new SimpleConfigurationLoader($this->options->corsConfiguration());

        $httpServer->start(stackMiddleware(
            $this->options->requestHandlerRouter($httpServer),
            new CorsMiddleware($corsLoader),
        ), new DefaultErrorHandler());


        // Await SIGINT or SIGTERM to be received.
        $signal = trapSignal([\SIGINT, \SIGTERM]);
        $logger->info(sprintf("Received signal %d, stopping HTTP server", $signal));

        $this->options->getHub()->stop();

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

    /**
     * Mimic the SocketHttpServer::createForDirectAccess() method.
     * @see https://github.com/amphp/http-server/issues/366
     */
    public static function createSocketHttpServer(
        PsrLogger $logger,
        int $connectionLimit = self::DEFAULT_CONNECTION_LIMIT,
        int $connectionLimitPerIp = self::DEFAULT_CONNECTIONS_PER_IP_LIMIT,
        ?int $concurrencyLimit = self::DEFAULT_CONCURRENCY_LIMIT,
        ?array $allowedMethods = AllowedMethodsMiddleware::DEFAULT_ALLOWED_METHODS,
        ?HttpDriverFactory $httpDriverFactory = null,
    ): SocketHttpServer {
        $serverSocketFactory = new ConnectionLimitingServerSocketFactory(new LocalSemaphore($connectionLimit));

        $logger->notice(\sprintf("Total client connections are limited to %d.", $connectionLimit));

        $clientFactory = new ConnectionLimitingClientFactory(
            new SocketClientFactory($logger),
            $logger,
            $connectionLimitPerIp,
        );

        $logger->notice(\sprintf(
            "Client connections are limited to %s per IP address (excluding localhost).",
            $connectionLimitPerIp,
        ));

        $middleware = [];

        if ($concurrencyLimit !== null) {
            $logger->notice(\sprintf("Request concurrency limited to %s simultaneous requests", $concurrencyLimit));
            $middleware[] = new ConcurrencyLimitingMiddleware($concurrencyLimit);
        }

        // We use custom settings for the compression middleware
        $middleware[] = new CompressionMiddleware(minimumLength: 12);

        return new SocketHttpServer(
            $logger,
            $serverSocketFactory,
            $clientFactory,
            $middleware,
            $allowedMethods,
            $httpDriverFactory,
        );
    }
}
