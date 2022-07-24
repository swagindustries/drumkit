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

use Psr\Log\LoggerInterface;
use SwagIndustries\MercureRouter\Exception\WrongOptionException;
use SwagIndustries\MercureRouter\Mercure\Hub;
use SwagIndustries\MercureRouter\Security\Signer;
use SwagIndustries\MercureRouter\Mercure\Store\InMemoryEventStore;
use SwagIndustries\MercureRouter\RequestHandlers\DevRequestHandlerRouterFactory;
use SwagIndustries\MercureRouter\RequestHandlers\RequestHandlerRouter;
use SwagIndustries\MercureRouter\RequestHandlers\RequestHandlerRouterFactory;
use SwagIndustries\MercureRouter\RequestHandlers\RequestHandlerRouterFactoryInterface;

class Options
{
    private const DEFAULT_SECURITY_KEY = '!ChangeMe!';
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

    private ?RequestHandlerRouterFactoryInterface $requestHandlerRouterFactory;

    private LoggerInterface $logger;

    public function __construct(
        string $sslCertificateFile,
        string $sslKeyFile,
        int $tlsPort = 443,
        int $unsecuredPort = 80,
        array $hosts = ['[::]', '0.0.0.0'], // open by default to the external network
        bool $devMode = false,
        LoggerInterface $logger = null,
        RequestHandlerRouterFactoryInterface $requestHandlerRouterFactory = null,
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

    public function requestHandlerRouter(): RequestHandlerRouter
    {
        $hub = new Hub(new InMemoryEventStore());
        return $this->getRequestHandlerRouterFactory()->createRequestHandlerRouter($hub);
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

    private function getRequestHandlerRouterFactory(): RequestHandlerRouterFactoryInterface
    {
        if ($this->requestHandlerRouterFactory !== null) {
            return $this->requestHandlerRouterFactory;
        }

        if ($this->devMode) {
            return $this->requestHandlerRouterFactory = new DevRequestHandlerRouterFactory();
        }

        return $this->requestHandlerRouterFactory = new RequestHandlerRouterFactory();
    }
}
