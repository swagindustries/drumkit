<?php

namespace SwagIndustries\MercureRouter\Security\Extractor;

use Amp\Http\Server\Request;

class CookieExtractor implements AuthorizationExtractorInterface
{
    // TODO: manage cookie origin if not already done in amphp
    private const COOKIE_NAME = 'mercureAuthorization';
    public function extract(Request $request): ?string
    {
        $bearer = $request->getCookie(self::COOKIE_NAME);

        return $bearer?->getValue();
    }
}
