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

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

class ServerConfiguration implements ConfigurationInterface
{
    public function getConfigTreeBuilder(): TreeBuilder
    {
        $builder = new TreeBuilder('root', 'array');

        $builder->getRootNode()
            ->children()
                ->arrayNode('network')
                    ->children()
                        ->scalarNode('tls_certificate_file')->end()
                        ->scalarNode('tls_key_file')->end()
                        ->integerNode('tls_port')->defaultValue(443)->end()
                        ->integerNode('unsecured_port')->defaultValue(80)->end()
                        ->arrayNode('hosts')->defaultValue(['127.0.0.1'])->scalarPrototype()->end()->end()
                        ->integerNode('stream_timeout')->defaultValue(120)->end()
                    ->end()
                ->end()
                ->arrayNode('security')
                    ->children()
                        ->arrayNode('subscriber')
                            ->children()
                                ->scalarNode('private_key')->end()
                                ->scalarNode('algorithm')->defaultValue('sha256')->end()
                            ->end()
                        ->end()
                        ->arrayNode('publisher')
                            ->children()
                                ->scalarNode('private_key')->end()
                                ->scalarNode('algorithm')->defaultValue('sha256')->end()
                            ->end()
                        ->end()
                        ->arrayNode('cors')
                            ->children()
                                ->arrayNode('origin')->scalarPrototype()->end()->end()
                                ->arrayNode('allowedHeaders')->scalarPrototype()->end()->end()
                                ->arrayNode('allowedExposableHeaders')->scalarPrototype()->end()->end()
                            ->end()
                        ->end()
                    ->end()
                ->end()
                ->arrayNode('features')
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->booleanNode('active_subscriptions')->defaultFalse()->end()
                    ->end()
                ->end()
            ->end();

        return $builder;
    }
}
