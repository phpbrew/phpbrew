<?php

namespace Satooshi\Bundle\CoverallsV1Bundle\Config;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

/**
 * Definition of .coveralls.yml configuration.
 *
 * # same as ruby
 * repo_token: your-token
 * repo_secret_token: your-token
 * service_name: travis-pro
 *
 * coverage_clover: build/logs/clover.xml
 * json_path: build/logs/coveralls-upload.json
 *
 * @author Kitamura Satoshi <with.no.parachute@gmail.com>
 */
class CoverallsConfiguration implements ConfigurationInterface
{
    // ConfigurationInterface

    /**
     * {@inheritdoc}
     *
     * @see \Symfony\Component\Config\Definition\ConfigurationInterface::getConfigTreeBuilder()
     */
    public function getConfigTreeBuilder()
    {
        // define configuration

        $treeBuilder = new TreeBuilder();
        $rootNode = $treeBuilder->root('coveralls');

        $rootNode
            ->children()
                // same as ruby lib
                ->scalarNode('repo_token')
                    ->defaultNull()
                ->end()
                ->scalarNode('repo_secret_token')
                    ->defaultNull()
                ->end()
                ->scalarNode('service_name')
                    ->defaultNull()
                ->end()
                ->variableNode('coverage_clover')
                    ->defaultValue('build/logs/clover.xml')
                ->end()
                ->scalarNode('json_path')
                    ->defaultValue('build/logs/coveralls-upload.json')
                ->end()
                ->booleanNode('exclude_no_stmt')
                    ->defaultFalse()
                ->end()
            ->end();

        return $treeBuilder;
    }
}
