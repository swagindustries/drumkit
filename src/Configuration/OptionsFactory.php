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

use League\Uri\Idna\Option;
use Nekland\Tools\StringTools;
use Psr\Log\LoggerInterface;
use SwagIndustries\MercureRouter\Exception\MissingOptionException;
use SwagIndustries\MercureRouter\Exception\UnexpectedValueException;
use SwagIndustries\MercureRouter\Http\CorsConfiguration;

class OptionsFactory
{
    public const OPTION_TLS_KEY = 'tls-key';
    public const OPTION_PORT_HTTPS = 'https-port';
    public const OPTION_PORT_HTTP = 'http-port';
    public const OPTION_FEATURE_SUBSCRIPTIONS = 'active-subscriptions';
    public const OPTION_SECURITY_SUBSCRIBER_ALG = 'security-subscriber-algorithm';
    public const OPTION_CORS_ORIGIN_KEY = 'corsOrigin';
    public const OPTION_SECURITY_PUBLISHER_KEY = 'security-publisher-key';
    public const OPTION_SECURITY_SUBSCRIBER_KEY = 'security-subscriber-key';
    public const OPTION_TLS_CERT = 'tls-cert';
    public const OPTION_SECURITY_PUBLISHER_ALG = 'security-publisher-algorithm';
    public const OPTION_DEV = 'dev';

    public static function fromFile(array $config, LoggerInterface $logger = null, bool $devMode = false): Options
    {
        $config['network']['tls_certificate_file'] = self::env(self::OPTION_TLS_CERT) ?? self::resolvePath($config['network']['tls_certificate_file']);
        $config['network']['tls_key_file'] = self::env(self::OPTION_TLS_KEY) ?? self::resolvePath($config['network']['tls_key_file']);

        if ($corsOrigin = self::env(self::OPTION_CORS_ORIGIN_KEY, 'array')) {
            $config['security']['cors']['origin'] = $corsOrigin;
        }

        return new Options(
            $config['network']['tls_certificate_file'],
            $config['network']['tls_key_file'],
            CorsConfiguration::createFromArray($config['security']['cors']),
            self::env(self::OPTION_PORT_HTTPS) ?? $config['network']['tls_port'],
            self::env(self::OPTION_PORT_HTTP) ?? $config['network']['unsecured_port'],
            $config['network']['hosts'],
            $config['network']['stream_timeout'],
            self::env(self::OPTION_FEATURE_SUBSCRIPTIONS, 'bool') ?? $config['features']['active_subscriptions'],
            $devMode,
            $logger,
            null,
            new SecurityOptions(
                self::env(self::OPTION_SECURITY_SUBSCRIBER_KEY) ?? $config['security']['subscriber']['private_key'],
                    self::env(self::OPTION_SECURITY_SUBSCRIBER_ALG) ?? $config['security']['subscriber']['algorithm']
            ),
            new SecurityOptions(
                self::env(self::OPTION_SECURITY_PUBLISHER_KEY) ?? $config['security']['publisher']['private_key'],
                self::env(self::OPTION_SECURITY_PUBLISHER_ALG) ?? $config['security']['publisher']['algorithm']
            )
        );
    }

    public static function fromCommandOptions(
        ?string $tlsCert,
        ?string $tlsKey,
        ?array $corsOrigin,
        ?string $subKey,
        ?string $subAlg,
        ?string $pubKey,
        ?string $pubAlg,
        bool $activeSubscriptions,
        bool $devMode,
        int $httpPort,
        int $httpsPort,
    ): Options {
        $tlsCert = self::env(self::OPTION_TLS_CERT) ?? $tlsCert;
        $tlsKey = self::env(self::OPTION_TLS_KEY) ?? $tlsKey;

        if (empty($tlsKey) || empty($tlsCert)) {
            throw new MissingOptionException('You must provide a TLS certificates to run the server');
        }

        $corsOrigin = self::env(self::OPTION_CORS_ORIGIN_KEY, 'array') ?? $corsOrigin;
        if ($devModeEnv = self::env(self::OPTION_DEV, 'bool') !== null) {
            $devMode = $devModeEnv;
        }
        if (empty($corsOrigin) && $devMode) {
            $corsOrigin = ['*'];
        }

        if (empty($corsOrigin)) {
            throw new MissingOptionException('You must specify cors origin to run the server');
        }

        $tlsKey = self::resolvePath($tlsKey);
        $tlsCert = self::resolvePath($tlsCert);
        $publisherSecurity = null;
        $subscriberSecurity = null;

        $subKey = self::env(self::OPTION_SECURITY_SUBSCRIBER_KEY) ?? $subKey;
        $subAlg = self::env(self::OPTION_SECURITY_SUBSCRIBER_ALG) ?? $subAlg;
        if ($subKey !== null) {
            $subscriberSecurity = new SecurityOptions(
                $subKey,
                $subAlg ?? Options::DEFAULT_SECURITY_ALG
            );
        }

        $pubKey = self::env(self::OPTION_SECURITY_PUBLISHER_KEY) ?? $pubKey;
        $pubAlg = self::env(self::OPTION_SECURITY_PUBLISHER_ALG) ?? $pubAlg;
        if ($pubKey !== null) {
            $publisherSecurity = $publisherSecurity ?? new SecurityOptions(
                $pubKey,
                $pubAlg ?? Options::DEFAULT_SECURITY_ALG
            );
        }

        return new Options(
            $tlsCert,
            $tlsKey,
            new CorsConfiguration($corsOrigin),
            tlsPort: $httpsPort,
            unsecuredPort: $httpPort,
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

    /**
     * @param string $optionName
     * @param 'string'|'bool'|'array' $expectedType
     */
    private static function env(string $optionName, string $expectedType = 'string'): null|string|bool|array
    {
        if (!in_array($expectedType, ['string', 'bool', 'array'])) {
            throw new UnexpectedValueException('Unexpected type "' . $expectedType . '" given');
        }

        $optionName = 'DRUMKIT_'.$optionName;
        $optionName = strtoupper($optionName);
        $optionName = str_replace('-', '_', $optionName);

        $option = getenv($optionName);
        if ($option === false) {
            return null;
        }

        if ($expectedType === 'array') {
            if (!is_array($option)) {
                return explode(',', $option);
            }

            return $option;
        }

        if ($expectedType == 'bool') {
            if ($option === 'true' || $option === '1' || $option === 1 || $option === true) {
                return true;
            }
            if ($option === 'false' || $option === '0' || $option === 0 || $option === false) {
                return false;
            }

            return null;
        }

        return $option;
    }
}
