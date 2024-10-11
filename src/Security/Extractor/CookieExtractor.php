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

class CookieExtractor implements AuthorizationExtractorInterface
{
    private const COOKIE_NAME = 'mercureAuthorization';
    public function extract(Request $request): ?string
    {
        $bearer = $request->getCookie(self::COOKIE_NAME);

        return $bearer?->getValue();
    }
}
