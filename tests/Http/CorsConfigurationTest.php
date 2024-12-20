<?php
/**
 * This file is a part of mercure-router-php package.
 *
 * (c) Swag Industries <nek.dev@gmail.com>
 *
 * For the full license, take a look to the LICENSE file
 * on the root directory of this project
 */

namespace SwagIndustries\MercureRouter\Test\Http;

use Cspray\Labrador\Http\Cors\Configuration;
use PHPUnit\Framework\TestCase;
use SwagIndustries\MercureRouter\Exception\UnexpectedValueException;
use SwagIndustries\MercureRouter\Http\CorsConfiguration;

class CorsConfigurationTest extends TestCase
{
    public function testItImplementsConfigurationInterface(): void
    {
        $subject = new CorsConfiguration(['*']);
        $this->assertInstanceOf(Configuration::class, $subject);
    }

    public function testItStoreAndReturnsValues(): void
    {
        $subject = new CorsConfiguration(
            ['*'],
            ['X-Custom-Auth'],
            ['X-Response-Header']
        );

        $this->assertEquals(['*'], $subject->getOrigins());
        $this->assertEquals(['X-Response-Header'], $subject->getExposableHeaders());
        $this->assertEquals(['X-Custom-Auth'], $subject->getAllowedHeaders());
        $this->assertEquals(['GET', 'POST'], $subject->getAllowedMethods());
    }

    public function testItValidatesMinimalInformationInCreationViaArray(): void
    {
        $this->expectException(UnexpectedValueException::class);
        CorsConfiguration::createFromArray([]);
    }
}