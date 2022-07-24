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
use SwagIndustries\MercureRouter\Security\Signer;

class SecurityOptions
{
    private string $key;
    private Signer $signer;

    public function __construct(string $key, Signer|string $signer)
    {
        $this->key = $key;
        $this->setSigner($signer);
    }

    public function getKey(): string
    {
        return $this->key;
    }

    public function getSigner(): Signer
    {
        return $this->signer;
    }

    private function setSigner(string|Signer $signer): void
    {
        if (!$signer instanceof Signer) {
            $signer = Signer::from($signer);
        }

        $this->signer = $signer;
    }
}
