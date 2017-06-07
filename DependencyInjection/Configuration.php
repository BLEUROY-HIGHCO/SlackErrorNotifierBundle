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
     *
     * @throws \RuntimeException
     */
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder();

        $root = $treeBuilder->root('highco_slack_error_notifier');

        /** @noinspection PhpUndefinedMethodInspection */
        $root
            ->children()
                ->booleanNode('handle404')
                    ->defaultValue(false)
                ->end()
                ->arrayNode('handleHTTPcodes')
                    ->prototype('scalar')
                        ->treatNullLike(array())
                    ->end()
                ->end()
                ->scalarNode('channel')
                    ->isRequired()
                    ->cannotBeEmpty()
                ->end()
                ->scalarNode('token')
                    ->isRequired()
                    ->cannotBeEmpty()
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
                        ->treatNullLike(array())
                    ->end()
                ->end()
                ->arrayNode('ignoredPhpErrors')
                    ->prototype('scalar')
                        ->treatNullLike(array())
                    ->end()
                ->end()
                ->arrayNode('ignoredIPs')
                    ->prototype('scalar')
                        ->treatNullLike(array())
                    ->end()
                ->end()
                ->scalarNode('ignoredAgentsPattern')
                    ->defaultValue('')
                ->end()
                ->scalarNode('ignoredUrlsPattern')
                    ->defaultValue('')
                ->end()
                ->arrayNode('formatter')
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->integerNode('firstClassLinesBeforeAfter')
                            ->isRequired()
                            ->cannotBeEmpty()
                            ->defaultValue(3)
                            ->min(0)
                        ->end()
                        ->integerNode('followingClassLinesBeforeAfter')
                            ->isRequired()
                            ->cannotBeEmpty()
                            ->defaultValue(0)
                            ->min(0)
                        ->end()
                        ->booleanNode('includeGetParameters')
                            ->isRequired()
                            ->cannotBeEmpty()
                            ->defaultValue(true)
                        ->end()
                        ->booleanNode('includePostParameters')
                            ->isRequired()
                            ->cannotBeEmpty()
                            ->defaultValue(true)
                        ->end()
                        ->booleanNode('includeRequestAttributes')
                            ->isRequired()
                            ->cannotBeEmpty()
                            ->defaultValue(true)
                        ->end()
                        ->booleanNode('includeRequestCookies')
                            ->isRequired()
                            ->cannotBeEmpty()
                            ->defaultValue(true)
                        ->end()
                        ->booleanNode('includeRequestHeaders')
                            ->isRequired()
                            ->cannotBeEmpty()
                            ->defaultValue(false)
                        ->end()
                        ->booleanNode('includeServerParameters')
                            ->isRequired()
                            ->cannotBeEmpty()
                            ->defaultValue(false)
                        ->end()
                        ->booleanNode('includeSessionAttributes')
                            ->isRequired()
                            ->cannotBeEmpty()
                            ->defaultValue(true)
                        ->end()
                    ->end()
                ->end()
            ->end();

        return $treeBuilder;
    }
}
