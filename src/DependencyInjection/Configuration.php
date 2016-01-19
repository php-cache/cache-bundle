<?php

/*
 * This file is part of php-cache\cache-bundle package.
 *
 * (c) 2015-2015 Aaron Scherer <aequasi@gmail.com>, Tobias Nyholm <tobias.nyholm@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Cache\CacheBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

/**
 * Class Configuration.
 *
 * @author Aaron Scherer <aequasi@gmail.com>
 */
class Configuration implements ConfigurationInterface
{
    /**
     * Generates the configuration tree builder.
     *
     * @return TreeBuilder The tree builder
     */
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder();
        $rootNode    = $treeBuilder->root('cache');

        $rootNode->children()
            ->append($this->addSessionSupportSection())
            ->append($this->addDoctrineSection())
            ->append($this->addRouterSection())
            ->append($this->addAnnotationSection())
            ->append($this->addSerializerSection())
            ->append($this->addValidationSection())
            ->append($this->addLoggingSection())
            ->end();

        return $treeBuilder;
    }

    /**
     * Configure the "cache.session" section.
     *
     * @return ArrayNodeDefinition
     */
    private function addSessionSupportSection()
    {
        $tree = new TreeBuilder();
        $node = $tree->root('session');

        $node
            ->canBeEnabled()
            ->addDefaultsIfNotSet()
            ->children()
                ->scalarNode('service_id')->isRequired()->end()
                ->booleanNode('use_tagging')->defaultTrue()->end()
                ->scalarNode('prefix')->defaultValue('session_')->end()
                ->scalarNode('ttl')->end()
            ->end();

        return $node;
    }

    /**
     * Configure the "cache.serializer" section.
     *
     * @return ArrayNodeDefinition
     */
    private function addSerializerSection()
    {
        $tree = new TreeBuilder();
        $node = $tree->root('serializer');

        $node
            ->canBeEnabled()
            ->addDefaultsIfNotSet()
            ->children()
            ->scalarNode('service_id')->isRequired()->end()
                ->booleanNode('use_tagging')->defaultTrue()->end()
            ->end();

        return $node;
    }

    /**
     * Configure the "cache.serializer" section.
     *
     * @return ArrayNodeDefinition
     */
    private function addValidationSection()
    {
        $tree = new TreeBuilder();
        $node = $tree->root('validation');

        $node
            ->canBeEnabled()
            ->addDefaultsIfNotSet()
            ->children()
                ->scalarNode('service_id')->isRequired()->end()
                ->booleanNode('use_tagging')->defaultTrue()->end()
            ->end();

        return $node;
    }

    /**
     * Configure the "cache.annotation" section.
     *
     * @return ArrayNodeDefinition
     */
    private function addAnnotationSection()
    {
        $tree = new TreeBuilder();
        $node = $tree->root('annotation');

        $node
            ->canBeEnabled()
            ->addDefaultsIfNotSet()
            ->children()
                ->scalarNode('service_id')->isRequired()->end()
                ->booleanNode('use_tagging')->defaultTrue()->end()
            ->end();

        return $node;
    }

    /**
     * @return ArrayNodeDefinition
     */
    private function addLoggingSection()
    {
        $tree = new TreeBuilder();
        $node = $tree->root('logging');

        $node
            ->canBeEnabled()
            ->addDefaultsIfNotSet()
            ->children()
                ->scalarNode('logger')->defaultValue('logger')->end()
                ->scalarNode('level')->defaultValue('info')->end()
            ->end();

        return $node;
    }

    /**
     * Configure the "cache.doctrine" section.
     *
     * @return ArrayNodeDefinition
     */
    private function addDoctrineSection()
    {
        $tree = new TreeBuilder();
        $node = $tree->root('doctrine');

        $node
            ->canBeEnabled()
            ->addDefaultsIfNotSet()
            ->children()
                ->booleanNode('use_tagging')
                    ->defaultTrue()
                ->end()
            ->end();

        $types = ['metadata', 'result', 'query'];
        foreach ($types as $type) {
            $node->children()
                    ->arrayNode($type)
                        ->canBeUnset()
                        ->children()
                            ->scalarNode('service_id')->isRequired()->end()
                            ->arrayNode('entity_managers')
                                ->defaultValue([])
                                ->beforeNormalization()
                                    ->ifString()
                                    ->then(
                                        function ($v) {
                                            return (array) $v;
                                        }
                                    )
                                    ->end()
                                    ->prototype('scalar')->end()
                                ->end()
                            ->arrayNode('document_managers')
                                ->defaultValue([])
                                ->beforeNormalization()
                                    ->ifString()
                                    ->then(
                                        function ($v) {
                                            return (array) $v;
                                        }
                                    )
                                ->end()
                                ->prototype('scalar')->end()
                            ->end()
                    ->end()
                ->end();
        }

        return $node;
    }

    /**
     * Configure the "cache.router" section.
     *
     * @return ArrayNodeDefinition
     */
    private function addRouterSection()
    {
        $tree = new TreeBuilder();
        $node = $tree->root('router');

        $node
            ->canBeEnabled()
            ->addDefaultsIfNotSet()
            ->children()
                ->integerNode('ttl')
                    ->defaultValue(604800)
                ->end()
                ->scalarNode('service_id')
                    ->isRequired()
                ->end()
            ->end();

        return $node;
    }
}
