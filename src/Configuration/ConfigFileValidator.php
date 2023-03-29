<?php

namespace SwagIndustries\MercureRouter\Configuration;

use SwagIndustries\MercureRouter\Exception\InvalidFileConfigurationException;
use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;
use Symfony\Component\Config\Definition\Processor;

class ConfigFileValidator
{
    public static function validate(array $fileContent): array
    {
        $processor = new Processor();

        try {
            return $processor->processConfiguration(
                new ServerConfiguration(),
                [$fileContent]
            );
        } catch (InvalidConfigurationException $e) {
            throw new InvalidFileConfigurationException($e);
        }
    }
}
