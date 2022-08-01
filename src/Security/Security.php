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
namespace SwagIndustries\MercureRouter\Security;

use Amp\Http\Server\Request;
use Lcobucci\Clock\SystemClock;
use Lcobucci\JWT\Configuration;
use Lcobucci\JWT\Signer\Key\InMemory;
use Lcobucci\JWT\Validation\Constraint\LooseValidAt;
use Lcobucci\JWT\Validation\Constraint\SignedWith;
use SwagIndustries\MercureRouter\Configuration\Options;
use SwagIndustries\MercureRouter\Configuration\SecurityOptions;
use SwagIndustries\MercureRouter\Exception\BearerNotFoundException;
use SwagIndustries\MercureRouter\Security\Extractor\AuthorizationExtractorInterface;

class Security
{
    public function __construct(
        private Options $options,
        private AuthorizationExtractorInterface $extractor,
        private Factory $configFactory
    ) { }

    public function validateSubscribeRequest(Request $request): bool
    {
        return $this->validateRequest($request, $this->options->subscriberSecurity());
    }

    public function validatePublishRequest(Request $request): bool
    {
        return $this->validateRequest($request, $this->options->publisherSecurity());
    }

    private function validateRequest(Request $request, SecurityOptions $options): bool
    {
        $token = $this->extractor->extract($request);
        if (null === $token) {
            throw new BearerNotFoundException();
        }
        $config = $this->configFactory->createJwtConfigurationFromMercureOptions($options);
        $token = $this->config->parser()->parse($token);

        return $this->config->validator()->validate(
            $token,
            new SignedWith($this->config->signer(), InMemory::plainText($options->getKey())),
            new LooseValidAt(new SystemClock(
                new DateTimeZone(\ini_get('date.timezone') ?: 'UTC')
            ))
        );
    }
}
