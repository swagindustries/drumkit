<?php

namespace SwagIndustries\MercureRouter\Configuration;

use League\Uri\Idna\Option;
use Nekland\Tools\StringTools;
use Psr\Log\LoggerInterface;
use SwagIndustries\MercureRouter\Http\CorsConfiguration;

class OptionsFactory
{
    public static function fromFile(array $config, LoggerInterface $logger = null, bool $devMode = false): Options
    {
        $config['network']['tls_certificate_file'] = self::resolvePath($config['network']['tls_certificate_file']);
        $config['network']['tls_key_file'] = self::resolvePath($config['network']['tls_key_file']);

        return new Options(
            $config['network']['tls_certificate_file'],
            $config['network']['tls_key_file'],
            CorsConfiguration::createFromArray($config['security']['cors']),
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
            )
        );
    }

    public static function fromCommandOptions(
        string $tlsCert,
        string $tlsKey,
        array $corsOrigin,
        ?string $subKey,
        ?string $subAlg,
        ?string $pubKey,
        ?string $pubAlg,
        bool $activeSubscriptions,
        bool $devMode
    ): Options {
        $tlsKey = self::resolvePath($tlsKey);
        $tlsCert = self::resolvePath($tlsCert);
        $publisherSecurity = null;
        $subscriberSecurity = null;

        if ($subKey !== null) {
            $subscriberSecurity = new SecurityOptions(
                $subKey,
                $subAlg ?? Options::DEFAULT_SECURITY_ALG
            );
        }

        if ($pubKey !== null) {
            $publisherSecurity = $publisherSecurity ?? new SecurityOptions(
                $pubKey,
                $pubAlg ?? Options::DEFAULT_SECURITY_ALG
            );
        }

        if (empty($corsOrigin) && $devMode) {
            $corsOrigin = ['*'];
        }

        return new Options(
            $tlsCert,
            $tlsKey,
            new CorsConfiguration($corsOrigin),
            activeSubscriptionEnabled: $activeSubscriptions,
            devMode: $devMode,
            subscriberSecurity: $subscriberSecurity,
            publisherSecurity:  $publisherSecurity,
        );
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
