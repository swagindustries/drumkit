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
use Lcobucci\JWT\JwtFacade;
use Lcobucci\JWT\Signer\InvalidKeyProvided;
use Lcobucci\JWT\Signer\Key\InMemory;
use Lcobucci\JWT\SodiumBase64Polyfill;
use Lcobucci\JWT\Token;
use Lcobucci\JWT\Token\InvalidTokenStructure;
use Lcobucci\JWT\Token\Plain;
use Lcobucci\JWT\Validation\Constraint\IssuedBy;
use Lcobucci\JWT\Validation\Constraint\LooseValidAt;
use Lcobucci\JWT\Validation\Constraint\SignedWith;
use Lcobucci\JWT\Validation\Constraint\StrictValidAt;
use SwagIndustries\MercureRouter\Configuration\Options;
use SwagIndustries\MercureRouter\Configuration\SecurityOptions;
use SwagIndustries\MercureRouter\Exception\BearerNotFoundException;
use SwagIndustries\MercureRouter\Exception\WrongBearerException;
use SwagIndustries\MercureRouter\Security\Extractor\AuthorizationExtractorInterface;
use Webmozart\Assert\Assert;

class Security
{
    public const ATTRIBUTE_JWT_PAYLOAD = 'jwt_payload';
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
        $requestToken = $this->extractor->extract($request);
        if (null === $requestToken) {
            throw new BearerNotFoundException();
        }
        $config = $this->configFactory->createJwtConfigurationFromMercureOptions($options);

        try {
            /** @var Plain $token */
            $token = $config->parser()->parse($requestToken);
            Assert::isInstanceOf($token, Plain::class);
            $request->setAttribute(self::ATTRIBUTE_JWT_PAYLOAD, $token->claims()->all());

            $result = $config->validator()->validate(
                $token,
                new SignedWith($config->signer(), InMemory::plainText($options->getKey())),
                new LooseValidAt(new SystemClock(
                    new \DateTimeZone(\ini_get('date.timezone') ?: 'UTC')
                ))
            );
        } catch (InvalidTokenStructure|InvalidKeyProvided $e) {
            $this->options->logger()->warning($e->getMessage());
            $result = false;
        }

        return $result;
    }
}
