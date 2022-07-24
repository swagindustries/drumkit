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
use Lcobucci\JWT\Configuration;
use Lcobucci\JWT\Signer\Key\InMemory;
use SwagIndustries\MercureRouter\Configuration\SecurityOptions;


class Factory
{
    public function __construct(private SecurityOptions $options)
    {
    }

    public function createJwtConfigurationFromMercureOptions()
    {
        return Configuration::forSymmetricSigner(
            $this->options->getSigner()->signer(),
            InMemory::plainText($this->options->getKey())
        );
    }
}
