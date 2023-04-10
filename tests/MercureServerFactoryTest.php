<?php

namespace SwagIndustries\MercureRouter\Test;

use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use SwagIndustries\MercureRouter\Configuration\Options;
use SwagIndustries\MercureRouter\MercureServerFactory;
use SwagIndustries\MercureRouter\Server;

class MercureServerFactoryTest extends TestCase
{
    use ProphecyTrait;
    public function testItCreatesAServer()
    {
        $options = $this->prophesize(Options::class)->reveal();
        $factory = new MercureServerFactory();
        $server = $factory->create($options);

        $this->assertInstanceOf(Server::class, $server);
    }
}
