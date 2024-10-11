<?php

namespace SwagIndustries\MercureRouter\Http;

use Cspray\Labrador\Http\Cors\Configuration;
use SwagIndustries\MercureRouter\Exception\UnexpectedValueException;

class CorsConfiguration implements Configuration
{
    /** @var string[] */
    private array $allowedOrigins;
    private array $allowedHeaders;
    private array $allowedExposableHeaders;
    private int $maxAge;

    public function __construct(array $allowedOrigins, array $allowedHeaders = [], array $allowedExposableHeaders = [])
    {
        if (empty($allowedOrigins)) {
            throw new UnexpectedValueException('CORS "origins" config should not be empty');
        }

        $this->allowedOrigins = $allowedOrigins;
        $this->allowedHeaders = $allowedHeaders;
        $this->allowedExposableHeaders = $allowedExposableHeaders;
        $this->maxAge = 86400;
    }

    public function getOrigins(): array
    {
        return $this->allowedOrigins;
    }

    public function getMaxAge(): ?int
    {
        return $this->maxAge;
    }

    public function getAllowedMethods(): array
    {
        return ['GET', 'POST'];
    }

    public function getAllowedHeaders(): array
    {
        return $this->allowedHeaders;
    }

    public function getExposableHeaders(): array
    {
        return $this->allowedExposableHeaders;
    }

    public function shouldAllowCredentials(): bool
    {
        return false;
    }

    public static function createFromArray(array $config): self
    {
        if (!isset($config['origin'])) {
            throw new UnexpectedValueException('CORS "origin" should not be empty');
        }

        return new self(
            $config['origin'],
            $config['allowedHeaders'] ?? [],
            $config['allowedExposableHeaders'] ?? []
        );
    }
}
