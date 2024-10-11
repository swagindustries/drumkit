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

namespace SwagIndustries\MercureRouter\Configuration;

use SwagIndustries\MercureRouter\Exception\InvalidFileConfigurationException;
use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;
use Symfony\Component\Config\Definition\Processor;

class ConfigFileValidator
{
    public function validate(array $fileContent): array
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
