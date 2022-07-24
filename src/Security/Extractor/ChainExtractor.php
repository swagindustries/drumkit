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

class ChainExtractor implements AuthorizationExtractorInterface
{
    /**
     * @param AuthorizationExtractorInterface[] $extractors
     */
    public function __construct(private array $extractors) {}

    public function extract(Request $request): ?string
    {
        foreach ($this->extractors as $extractor) {
            $result = $extractor->extract($request);

            if ($result !== null) {
                return $result;
            }
        }

        return null;
    }
}
