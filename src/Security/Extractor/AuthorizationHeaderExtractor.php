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

namespace SwagIndustries\MercureRouter\Security\Extractor;

use Amp\Http\Server\Request;

class AuthorizationHeaderExtractor implements AuthorizationExtractorInterface
{
    private const BEARER_PREFIX = 'Bearer ';

    public function extract(Request $request): ?string
    {
        $authorization = $request->getHeader('authorization');
        if (null === $authorization) {
            return null;
        }

        $bearer = substr($authorization, strlen(self::BEARER_PREFIX));

        return $bearer ?: null;
    }
}
