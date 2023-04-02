<?php

namespace SwagIndustries\MercureRouter\Configuration;

use Nekland\Tools\StringTools;
use Psr\Log\LoggerInterface;

class OptionsFactory
{
    public static function fromFile(array $config, LoggerInterface $logger = null, bool $devMode = false): Options
    {
        $config['network']['tls_certificate_file'] = self::resolvePath($config['network']['tls_certificate_file']);
        $config['network']['tls_key_file'] = self::resolvePath($config['network']['tls_key_file']);

        return new Options(
            $config['network']['tls_certificate_file'],
            $config['network']['tls_key_file'],
            $config['network']['tls_port'],
            $config['network']['unsecured_port'],
            $config['network']['hosts'],
            $config['network']['stream_timeout'],
            $config['features']['active_subscriptions'],
            $devMode,
            $logger,
            null,
            new SecurityOptions(
                $config['security']['subscriber']['private_key'],
                $config['security']['subscriber']['algorithm']
            ),
            new SecurityOptions(
                $config['security']['publisher']['private_key'],
                $config['security']['publisher']['algorithm']
            ),
            $config['security']['cors']['origin']
        );
    }

    public static function fromCommandOptions(
        string $tlsCert,
        string $tlsKey,
        bool $activeSubscriptions,
        bool $devMode
    ): Options {
        $tlsKey = self::resolvePath($tlsKey);
        $tlsCert = self::resolvePath($tlsCert);

        return new Options($tlsCert, $tlsKey, activeSubscriptionEnabled: $activeSubscriptions, devMode: $devMode);
    }

    private static function resolvePath(string $path): string
    {
        $workingDirectory = getcwd();
        if (!StringTools::startsWith($path, '/')) {
            return $workingDirectory . '/' . $path;
        }

        return $path;
    }
}
