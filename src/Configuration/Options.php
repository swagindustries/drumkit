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
use SwagIndustries\MercureRouter\RequestHandlers\RequestHandlerRouter;
use SwagIndustries\MercureRouter\RequestHandlers\RequestHandlerRouterFactory;
use SwagIndustries\MercureRouter\RequestHandlers\RequestHandlerRouterFactoryInterface;

class Options
{
    private string $certificate;
    private string $key;

    private int $tlsPort;
    private int $unsecuredPort;
    private array $hosts;

    private RequestHandlerRouterFactoryInterface $requestHandlerRouterFactory;

    private LoggerInterface $logger;

    public function __construct(
        string $certificateFile,
        string $keyFile,
        int $tlsPort = 443,
        int $unsecuredPort = 80,
        array $hosts = ['[::]', '0.0.0.0'], // open by default to the external network
        LoggerInterface $logger = null,
        RequestHandlerRouterFactoryInterface $requestHandlerRouterFactory = null,
    ) {
        $this->setCertificate($certificateFile);
        $this->setKey($keyFile);
        $this->tlsPort = $tlsPort;
        $this->unsecuredPort = $unsecuredPort;
        $this->hosts = $hosts;
        $this->logger = $logger ?? DefaultLoggerFactory::createDefaultLogger();
        $this->requestHandlerRouterFactory = $requestHandlerRouterFactory ?? new RequestHandlerRouterFactory();
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
        return $this->requestHandlerRouterFactory->createRequestHandlerRouter();
    }

    public function logger(): LoggerInterface
    {
        return $this->logger;
    }

    private function setKey(string $key)
    {
        if (!file_exists($key)) {
            throw new WrongOptionException("Cannot found certificate file '$key'");
        }

        $this->key = $key;
    }

    private function setCertificate(string $certificate)
    {
        if (!file_exists($certificate)) {
            throw new WrongOptionException("Cannot found certificate file '$certificate'");
        }

        $this->certificate = $certificate;
    }
}
