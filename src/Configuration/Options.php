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

namespace SwagIndustries\MercureRouter\Configuration;

use Amp\Http\Server\Router;
use Amp\Http\Server\SocketHttpServer;
use Psr\Log\LoggerInterface;
use SwagIndustries\MercureRouter\Exception\MissingSecurityConfigurationException;
use SwagIndustries\MercureRouter\Exception\WrongOptionException;
use SwagIndustries\MercureRouter\Http\Router\DevRouterFactory;
use SwagIndustries\MercureRouter\Http\Router\RouterFactory;
use SwagIndustries\MercureRouter\Mercure\Hub;
use SwagIndustries\MercureRouter\Security\Extractor\AuthorizationExtractorInterface;
use SwagIndustries\MercureRouter\Security\Extractor\AuthorizationHeaderExtractor;
use SwagIndustries\MercureRouter\Security\Extractor\ChainExtractor;
use SwagIndustries\MercureRouter\Security\Extractor\CookieExtractor;
use SwagIndustries\MercureRouter\Security\Factory;
use SwagIndustries\MercureRouter\Security\Security;
use SwagIndustries\MercureRouter\Security\Signer;
use SwagIndustries\MercureRouter\Mercure\Store\InMemoryEventStore;

class Options
{
    private const DEFAULT_SECURITY_KEY = '!ChangeThisMercureHubJWTSecretKey!';
    private const DEFAULT_SECURITY_ALG = Signer::SHA_256;

    // Debug mode (enable a UI to test mercure features & debug logging)
    private bool $devMode;

    // HTTP config
    private int $tlsPort;
    private int $unsecuredPort;
    private array $hosts;
    private int $streamTimeout; // Timeout in seconds, default to 45s

    // SSL configuration
    private string $certificate;
    private string $key;

    // Security config
    private SecurityOptions $subscriberSecurity;
    private SecurityOptions $publisherSecurity;

    // Mercure-specific options
    // see https://mercure.rocks/spec#active-subscriptions
    // Test it in dev mode
    private bool $activeSubscriptionEnabled;

    // No dependency injection in this project
    // so dependencies are managed here
    private ?RouterFactory $requestHandlerRouterFactory;
    private ?Security $security;
    private array $corsOrigin;

    private LoggerInterface $logger;

    public function __construct(
        string $sslCertificateFile,
        string $sslKeyFile,
        int $tlsPort = 443,
        int $unsecuredPort = 80,
        array $hosts = ['[::]', '0.0.0.0'], // open by default to the external network
        int $streamTimeout = 120, // official client is 45
        bool $activeSubscriptionEnabled = false,
        // The dev mode enables the UI/Debugger
        bool $devMode = false,
        LoggerInterface $logger = null,
        RouterFactory $requestHandlerRouterFactory = null,
        SecurityOptions $subscriberSecurity = null,
        SecurityOptions $publisherSecurity = null,
        array $corsOrigin = []
    ) {
        $this->setCertificate($sslCertificateFile);
        $this->setKey($sslKeyFile);
        $this->tlsPort = $tlsPort;
        $this->unsecuredPort = $unsecuredPort;
        $this->hosts = $hosts;
        $this->streamTimeout = $streamTimeout;
        $this->devMode = $devMode;
        $this->activeSubscriptionEnabled = $activeSubscriptionEnabled;
        $this->logger = $logger ?? DefaultLoggerFactory::createDefaultLogger($devMode);
        $this->requestHandlerRouterFactory = $requestHandlerRouterFactory;
        $this->security = null;
        $this->corsOrigin = $corsOrigin;

        if ($this->devMode) {
            $this->subscriberSecurity = $subscriberSecurity ?? new SecurityOptions(
                self::DEFAULT_SECURITY_KEY,
                self::DEFAULT_SECURITY_ALG
            );
            $this->publisherSecurity = $publisherSecurity ?? new SecurityOptions(
                self::DEFAULT_SECURITY_KEY,
                self::DEFAULT_SECURITY_ALG
            );
        } else {
            $this->subscriberSecurity = $subscriberSecurity ?? throw new MissingSecurityConfigurationException();
            $this->publisherSecurity = $publisherSecurity ?? throw new MissingSecurityConfigurationException();
        }
    }

    public function certificate(): string
    {
        return $this->certificate;
    }

    public function key(): string
    {
        return $this->key;
    }

    public function hosts(): array
    {
        return $this->hosts;
    }

    public function isDevMode(): bool
    {
        return $this->devMode;
    }

    public function tlsPort(): int
    {
        return $this->tlsPort;
    }

    public function unsecuredPort(): int
    {
        return $this->unsecuredPort;
    }

    public function streamTimeout(): int
    {
        return $this->streamTimeout;
    }

    public function requestHandlerRouter(SocketHttpServer $httpServer): Router
    {
        $hub = new Hub(new InMemoryEventStore());
        return $this->getRequestHandlerRouterFactory()->createRouter($httpServer, $hub, $this->getSecurity());
    }

    public function subscriberSecurity(): SecurityOptions
    {
        return $this->subscriberSecurity;
    }

    public function publisherSecurity(): SecurityOptions
    {
        return $this->publisherSecurity;
    }

    public function logger(): LoggerInterface
    {
        return $this->logger;
    }

    public function corsOrigins(): array
    {
        return $this->corsOrigin;
    }

    private function setKey(string $key): void
    {
        if (!file_exists($key)) {
            throw new WrongOptionException("Cannot found certificate file '$key'");
        }

        $this->key = $key;
    }

    private function setCertificate(string $certificate): void
    {
        if (!file_exists($certificate)) {
            throw new WrongOptionException("Cannot found certificate file '$certificate'");
        }

        $this->certificate = $certificate;
    }

    private function getRequestHandlerRouterFactory(): RouterFactory
    {
        if ($this->requestHandlerRouterFactory !== null) {
            return $this->requestHandlerRouterFactory;
        }

        if ($this->devMode) {
            return $this->requestHandlerRouterFactory = new DevRouterFactory($this->activeSubscriptionEnabled, $this->logger);
        }

        return $this->requestHandlerRouterFactory = new RouterFactory($this->activeSubscriptionEnabled, $this->logger);
    }

    private function getAuthorizationExtractor(): AuthorizationExtractorInterface
    {
        return new ChainExtractor([
            new AuthorizationHeaderExtractor(),
            new CookieExtractor()
        ]);
    }

    private function getSecurity()
    {
        if ($this->security !== null) {
            return $this->security;
        }

        return $this->security = new Security($this, $this->getAuthorizationExtractor(), new Factory());
    }
}
