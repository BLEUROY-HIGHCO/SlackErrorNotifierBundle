<?php

namespace Highco\SlackErrorNotifierBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

/**
 * Configuration for ElaoErrorNotifierBundle
 */
class Configuration implements ConfigurationInterface
{
    /**
     * Get config tree
     *
     * @return TreeBuilder
     */
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder();

        $root = $treeBuilder->root('highco_slack_error_notifier');

        $root
            ->children()
                ->booleanNode('handle404')
                    ->defaultValue(false)
                ->end()
                ->arrayNode('handleHTTPcodes')
                    ->prototype('scalar')
                        ->treatNullLike([])
                    ->end()
                ->end()
                ->scalarNode('channel')
                    ->defaultValue('channel')
                ->end()
                ->scalarNode('repeatTimeout')
                    ->defaultValue(false)
                ->end()
                ->booleanNode('handlePHPWarnings')
                    ->defaultValue(false)
                ->end()
                ->booleanNode('handlePHPErrors')
                    ->defaultValue(false)
                ->end()
                ->booleanNode('handleSilentErrors')
                    ->defaultValue(false)
                ->end()
                ->arrayNode('ignoredClasses')
                    ->prototype('scalar')
                        ->treatNullLike([])
                    ->end()
                ->end()
                ->arrayNode('ignoredPhpErrors')
                    ->prototype('scalar')
                        ->treatNullLike([])
                    ->end()
                ->end()
                ->arrayNode('ignoredIPs')
                    ->prototype('scalar')
                        ->treatNullLike([])
                    ->end()
                ->end()
                ->scalarNode('ignoredAgentsPattern')
                    ->defaultValue('')
                ->end()
                ->scalarNode('ignoredUrlsPattern')
                    ->defaultValue('')
                ->end()
            ->end();

        return $treeBuilder;
    }
}
