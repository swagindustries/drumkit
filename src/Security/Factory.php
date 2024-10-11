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

use Lcobucci\JWT\Configuration;
use Lcobucci\JWT\Signer\Key\InMemory;
use SwagIndustries\MercureRouter\Configuration\SecurityOptions;


class Factory
{
    public function createJwtConfigurationFromMercureOptions(SecurityOptions $options): Configuration
    {
        return Configuration::forSymmetricSigner(
            $options->getSigner()->signer(),
            InMemory::plainText($options->getKey())
        );
    }
}
