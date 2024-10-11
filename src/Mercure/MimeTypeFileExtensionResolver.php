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

namespace SwagIndustries\MercureRouter\Mercure;

use SwagIndustries\MercureRouter\Exception\CannotMatchMimetypeException;

class MimeTypeFileExtensionResolver
{
    private const MIME_TYPE_MATCHING = [
        'application/ld+json' => 'jsonld',
        'application/json' => 'json',
    ];
    public function resolve(string $mimeType): string
    {
        return self::MIME_TYPE_MATCHING[$mimeType] ?? throw new CannotMatchMimetypeException("Impossible to find an extension to given mimetype '$mimeType'");
    }
}
