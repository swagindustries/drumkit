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
use Psr\Log\LoggerInterface;
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

    // Debug mode
    private bool $devMode;

    // HTTP config
    private int $tlsPort;
    private int $unsecuredPort;
    private array $hosts;

    // SSL configuration
    private string $certificate;
    private string $key;

    // Security config
    private SecurityOptions $subscriberSecurity;
    private SecurityOptions $publisherSecurity;

    // No dependency injection in this project
    // so dependencies are managed here
    private ?RouterFactory $requestHandlerRouterFactory;
    private ?Security $security;

    private LoggerInterface $logger;

    public function __construct(
        string $sslCertificateFile,
        string $sslKeyFile,
        int $tlsPort = 443,
        int $unsecuredPort = 80,
        array $hosts = ['[::]', '0.0.0.0'], // open by default to the external network
        bool $devMode = false,
        LoggerInterface $logger = null,
        RouterFactory $requestHandlerRouterFactory = null,
        SecurityOptions $subscriberSecurity = null,
        SecurityOptions $publisherSecurity = null,
    ) {
        $this->setCertificate($sslCertificateFile);
        $this->setKey($sslKeyFile);
        $this->tlsPort = $tlsPort;
        $this->unsecuredPort = $unsecuredPort;
        $this->hosts = $hosts;
        $this->devMode = $devMode;
        $this->logger = $logger ?? DefaultLoggerFactory::createDefaultLogger();
        $this->requestHandlerRouterFactory = $requestHandlerRouterFactory;
        $this->subscriberSecurity = $subscriberSecurity ?? new SecurityOptions(self::DEFAULT_SECURITY_KEY, self::DEFAULT_SECURITY_ALG);
        $this->publisherSecurity = $publisherSecurity ?? new SecurityOptions(self::DEFAULT_SECURITY_KEY, self::DEFAULT_SECURITY_ALG);
        $this->security = null;
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

    public function requestHandlerRouter(): Router
    {
        $hub = new Hub(new InMemoryEventStore());
        return $this->getRequestHandlerRouterFactory()->createRouter($hub, $this->getSecurity());
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
            return $this->requestHandlerRouterFactory = new DevRouterFactory($this->logger);
        }

        return $this->requestHandlerRouterFactory = new RouterFactory($this->logger);
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
