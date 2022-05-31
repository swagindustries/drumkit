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
namespace SwagIndustries\MercureRouter\Http;

/**
 * Not the best query parser but this one is compatible with the mercure protocol regardless the function `parse_str()`
 * Related to \Symfony\Component\Mercure\Internal\QueryBuilder
 */
class QueryParser
{
    public static function parse(string $query): array
    {
        $parameters = explode('&', $query);
        $resolvedParameters = [];

        foreach ($parameters as $parameter) {
            if (!str_contains($parameter, '=')) {
                continue;
            }
            [$parameterName, $parameterValue] = explode('=', $parameter);

            if (isset($resolvedParameters[$parameterName])) {
                if (is_string($resolvedParameters[$parameterName])) {
                    $resolvedParameters[$parameterName] = [$resolvedParameters[$parameterName]];
                }
                $resolvedParameters[$parameterName][] = urldecode($parameterValue);
            } else {
                $resolvedParameters[$parameterName] = urldecode($parameterValue);
            }
        }

        return $resolvedParameters;
    }
}
