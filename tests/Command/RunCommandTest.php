<?php

namespace SwagIndustries\MercureRouter\Test\Command;

use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use SwagIndustries\MercureRouter\Command\RunCommand;
use SwagIndustries\MercureRouter\Configuration\Options;
use SwagIndustries\MercureRouter\MercureServerFactory;
use SwagIndustries\MercureRouter\Server;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;

class RunCommandTest extends TestCase
{
    use ProphecyTrait;
    private CommandTester $commandTester;
    private $fakeServerFactory;
    protected function setUp(): void
    {
        parent::setUp();

        $this->fakeServerFactory = $this->prophesize(MercureServerFactory::class);
        $application = new Application();
        $application->add(new RunCommand(
            serverFactory: $this->fakeServerFactory->reveal()
        ));

        $command = $application->find('run');

        $this->commandTester = new CommandTester($command);
    }

    public function testItRunWithConfigFile()
    {
        $server = $this->prophesize(Server::class)->reveal();
        $this->fakeServerFactory->create(Argument::type(Options::class))
            ->shouldBeCalled()
            ->willReturn($server);

        $this->commandTester->execute(['--config' => __DIR__ . '/../fixtures/full_configuration.json']);
        $this->commandTester->assertCommandIsSuccessful();
    }

    public function testItRunWithoutConfigFile()
    {
        $server = $this->prophesize(Server::class)->reveal();
        $this->fakeServerFactory->create(Argument::type(Options::class))
            ->shouldBeCalled()
            ->willReturn($server);

        $this->commandTester->execute([
            '--tls-key' => __DIR__ . '/../../ssl/mercure-router.local-key.pem',
            '--tls-cert' => __DIR__ . '/../../ssl/mercure-router.local.pem',
            '--active-subscriptions' => true,
            '--dev' => true
        ]);
        $this->commandTester->assertCommandIsSuccessful();
    }

    public function testItFailsIfNoConfigOrOptionsProvided()
    {
        $this->commandTester->execute([]);

        $this->assertStringContainsString(
            'You need to provide at least TLS certificates to run the server',
            $this->commandTester->getDisplay()
        );

        $this->assertEquals(Command::FAILURE, $this->commandTester->getStatusCode());
    }
}
