<?php

namespace SwagIndustries\MercureRouter\Test\Configuration;

use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use SwagIndustries\MercureRouter\Configuration\DefaultLoggerFactory;

class DefaultLoggerFactoryTest extends TestCase
{
    public function testItCreateALogger()
    {
        $logger = DefaultLoggerFactory::createDefaultLogger();
        $this->assertInstanceOf(LoggerInterface::class, $logger);
    }
}
