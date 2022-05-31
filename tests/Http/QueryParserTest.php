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
namespace SwagIndustries\MercureRouter\Test\Http;

use PHPUnit\Framework\TestCase;
use SwagIndustries\MercureRouter\Http\QueryParser;

class QueryParserTest extends TestCase
{
    /**
     * @dataProvider provideQueryExamples
     */
    public function testItParseCorrectlyMercureQuery($query, $expectedParameters)
    {
        $parameters = QueryParser::parse($query);

        $this->assertEquals($parameters, $expectedParameters);
    }

    public function provideQueryExamples()
    {
        // Creators of mercure wants the world burn
        yield 'It returns an array of topics when many topics given' => [
            'topic=foo&topic=bar&hello=world&topic=baz',
            ['topic' => ['foo', 'bar', 'baz'], 'hello' => 'world']
        ];

        // Because it is not required for a mercure router, this is very specific
        yield 'It does not support standard PHP arrays in query' => [
            'topic[0]=foo&topic[1]=bar',
            ['topic[0]' => 'foo', 'topic[1]' => 'bar']
        ];
    }
}
