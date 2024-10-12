<?php

namespace SwagIndustries\MercureRouter\Test\Functional;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Process\Process;

abstract class AbstractFunctionalTest extends TestCase
{
    public const UNSECURED_PORT = 8080;
    public const TLS_PORT = 4443;
    private Process $process;
    protected function setUp(): void
    {
        $this->process = new Process(
            [
                'bin/drumkit',
                '--tls-cert=ssl/ci.mercure-router.local.pem',
                '--tls-key=ssl/ci.mercure-router.local-key.pem',
                '--dev',
                '--active-subscriptions',
                '--http-port='.self::UNSECURED_PORT,
                '--https-port='.self::TLS_PORT,
            ]
        );

        $this->process->start();
        $this->process->waitUntil(function ($type, $buffer) {
            return str_contains($buffer, 'Listening on');
        });
    }

    protected function tearDown(): void
    {
        $outputDir = __DIR__.'/../../logs';
        if (!is_dir($outputDir)) {
            mkdir($outputDir);
        }

        $file = $this->getName();
        file_put_contents($outputDir.'/'.$file.'.out',$this->process->getOutput());
        file_put_contents($outputDir.'/'.$file.'.err',$this->process->getErrorOutput());
        $this->process->stop();
    }
}
