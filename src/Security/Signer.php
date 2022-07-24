<?php

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
