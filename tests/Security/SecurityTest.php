<?php

namespace SwagIndustries\MercureRouter\Test\Security;

use Amp\Http\Server\Driver\Client;
use Amp\Http\Server\Request;
use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Psr\Http\Message\UriInterface;
use Psr\Log\NullLogger;
use SwagIndustries\MercureRouter\Configuration\Options;
use SwagIndustries\MercureRouter\Configuration\SecurityOptions;
use SwagIndustries\MercureRouter\Security\Extractor\AuthorizationExtractorInterface;
use SwagIndustries\MercureRouter\Security\Factory;
use SwagIndustries\MercureRouter\Security\Security;
use SwagIndustries\MercureRouter\Security\Signer;
use SwagIndustries\MercureRouter\Test\fixtures\Fixtures;

class SecurityTest extends TestCase
{
    use ProphecyTrait;

    public function testItValidateToken()
    {
        $options = new Options(Fixtures::stub, Fixtures::stub, logger: new NullLogger());
        $token = 'eyJhbGciOiJIUzI1NiJ9.eyJtZXJjdXJlIjp7InB1Ymxpc2giOlsiKiJdLCJzdWJzY3JpYmUiOlsiaHR0cHM6Ly9leGFtcGxlLmNvbS9teS1wcml2YXRlLXRvcGljIl0sInBheWxvYWQiOnsidXNlciI6Imh0dHBzOi8vZXhhbXBsZS5jb20vdXNlcnMvZHVuZ2xhcyIsInJlbW90ZUFkZHIiOiIxMjcuMC4wLjEifX0sImV4cCI6MTkzNjczODk4MX0.Cs_5benTtDWLQG_xZQIfGSBwhHj7Oa9Zo28c9_EY3WY';

        $request = $this->fakeRequest();
        /** @var AuthorizationExtractorInterface|ObjectProphecy $provider */
        $provider = $this->prophesize(AuthorizationExtractorInterface::class);
        $provider->extract($request)->willReturn($token);

        $security = new Security($options, $provider->reveal(), new Factory());

        $this->assertTrue($security->validateSubscribeRequest($request));
    }

    public function testItInvalidatesToken()
    {
        $options = new Options(
            Fixtures::stub,
            Fixtures::stub,
            subscriberSecurity: new SecurityOptions('key that does not match jwt', Signer::SHA_256),
            logger: new NullLogger()
        );
        $token = 'eyJhbGciOiJIUzI1NiJ9.eyJtZXJjdXJlIjp7InB1Ymxpc2giOlsiKiJdLCJzdWJzY3JpYmUiOlsiaHR0cHM6Ly9leGFtcGxlLmNvbS9teS1wcml2YXRlLXRvcGljIl0sInBheWxvYWQiOnsidXNlciI6Imh0dHBzOi8vZXhhbXBsZS5jb20vdXNlcnMvZHVuZ2xhcyIsInJlbW90ZUFkZHIiOiIxMjcuMC4wLjEifX0sImV4cCI6MTY3NzUzOTEyMH0.sYrrWMt-H2KG9wsJc5qwv27vfe-2fslUoOqRxEma_fY';

        $request = $this->fakeRequest();
        /** @var AuthorizationExtractorInterface|ObjectProphecy $provider */
        $provider = $this->prophesize(AuthorizationExtractorInterface::class);
        $provider->extract($request)->willReturn($token);

        $security = new Security($options, $provider->reveal(), new Factory());

        $this->assertFalse($security->validateSubscribeRequest($request));
    }

    public function testItDoesNotHardFailForTooShortToken()
    {
        $options = new Options(
            Fixtures::stub,
            Fixtures::stub,
            subscriberSecurity: new SecurityOptions('key that does not match jwt', Signer::SHA_256),
            logger: new NullLogger()
        );
        $token = 'eyJhbGciOiJIUzI1NiJ9.eyJtZXJjdXJlIjp7InB1Ymxpc2giOlsiKiJdLCJzdWJzY3JpYmUiOlsiaHR0cHM6Ly9leGFtcGxlLmNvbS9teS1wcml2YXRlLXRvcGljIiwie3NjaGVtZX06Ly97K2hvc3R9L2RlbW8vYm9va3Mve2lkfS5qc29ubGQiLCIvLndlbGwta25vd24vbWVyY3VyZS9zdWJzY3JpcHRpb25zey90b3BpY317L3N1YnNjcmliZXJ9Il0sInBheWxvYWQiOnsidXNlciI6Imh0dHBzOi8vZXhhbXBsZS5jb20vdXNlcnMvZHVuZ2xhcyIsInJlbW90ZUFkZHIiOiIxMjcuMC4wLjEifX19.z5YrkHwtkz3O_nOnhC_FP7_bmeISe3eykAkGbAl5K7c';

        $request = $this->fakeRequest();
        /** @var AuthorizationExtractorInterface|ObjectProphecy $provider */
        $provider = $this->prophesize(AuthorizationExtractorInterface::class);
        $provider->extract($request)->willReturn($token);

        $security = new Security($options, $provider->reveal(), new Factory());

        $this->assertFalse($security->validateSubscribeRequest($request));
    }

    private function fakeRequest(): Request
    {
        return new Request(
            $this->prophesize(Client::class)->reveal(),
            'GET',
            $this->prophesize(UriInterface::class)->reveal()
        );
    }
}
