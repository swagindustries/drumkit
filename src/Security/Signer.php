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

use Lcobucci\JWT\Signer as ActualSigner;
use Lcobucci\JWT\Signer\Hmac\Sha256;

enum Signer: string
{
    case SHA_256 = 'sha256';

    public function signer(): ActualSigner
    {
        return match ($this) {
            Signer::SHA_256 => new Sha256()
        };
    }
}
