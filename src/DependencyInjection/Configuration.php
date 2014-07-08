<?php

/**
 * @author    Aaron Scherer <aequasi@gmail.com>
 * @date      2013
 * @license   http://www.apache.org/licenses/LICENSE-2.0.html Apache License, Version 2.0
 */

namespace Aequasi\Bundle\CacheBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

/**
 * Class Configuration
 *
 * @author Aaron Scherer <aequasi@gmail.com>
 */
class Configuration implements ConfigurationInterface
{

    /**
     * @var bool
     */
    private $debug;

    /**
     * Constructor
     *
     * @param Boolean $debug Whether to use the debug mode
     */
    public function __construct($debug)
    {
        $this->debug = (Boolean)$debug;
    }

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
            ->append($this->getClustersNode())
            ->append($this->addSessionSupportSection())
            ->append($this->addDoctrineSection())
            ->append($this->addRouterSection())
            ->end();

        return $treeBuilder;
    }

    /**
     * @return ArrayNodeDefinition
     */
    private function getClustersNode()
    {
        $treeBuilder = new TreeBuilder();
        $node        = $treeBuilder->root('instances');

        $node
            ->requiresAtLeastOneElement()
            ->addDefaultChildrenIfNoneSet('default')
            ->useAttributeAsKey('name')
            ->prototype('array')
                ->children()
                    ->enumNode('type')
                        ->values(array('redis', 'php_file', 'file_system', 'array', 'memcached', 'apc'))
                    ->end()
                    ->scalarNode('id')
                        ->defaultNull()
                    ->end()
                    ->scalarNode('namespace')
                        ->defaultNull()
                        ->info("Namespace for doctrine keys.")
                    ->end()
                    ->integerNode('database')
                        ->defaultNull()
                        ->info("For Redis: Specify what database you want.")
                    ->end()
                    ->scalarNode('persistent')
                        ->defaultNull()
                        ->beforeNormalization()
                            ->ifTrue(
                                function($v) { 
                                    return $v === 'true' || $v === 'false'; 
                                }
                            )
                            ->then(
                                function($v) { 
                                    return (bool) $v; 
                                }
                            )
                        ->end()
                        ->info("For Redis and Memcached: Specify the persistent id if you want persistent connections.")
                    ->end()
                    ->scalarNode('auth_password')
                        ->info("For Redis: Authorization info.")
                    ->end()
                    ->scalarNode('directory')
                        ->info("For File System and PHP File: Directory to store cache.")
                        ->defaultNull()
                    ->end()
                    ->scalarNode('extension')
                        ->info("For File System and PHP File: Extension to use.")
                        ->defaultNull()
                    ->end()
                    ->arrayNode('options')
                        ->info("Options for Redis and Memcached.")
                    ->end()
                    ->arrayNode('hosts')
                        ->prototype('array')
                            ->children()
                                ->scalarNode('host')
                                    ->defaultNull()
                                ->end()
                                ->scalarNode('port')
                                    ->defaultNull()
                                    ->validate()
                                        ->ifTrue(
                                            function ($v) {
                                                return !is_null($v) && !is_numeric($v);
                                            }
                                        )
                                        ->thenInvalid("Host port must be numeric")
                                    ->end()
                                ->end()
                                ->scalarNode('weight')
                                    ->info("For Memcached: Weight for given host.")
                                    ->defaultNull()
                                    ->validate()
                                        ->ifTrue(
                                            function ($v) {
                                                return !is_null($v) && !is_numeric($v);
                                            }
                                        )
                                        ->thenInvalid('host weight must be numeric')
                                    ->end()
                                ->end()
                                ->scalarNode('timeout')
                                    ->info("For Redis and Memcache: Timeout for the given host.")
                                    ->defaultNull()
                                    ->validate()
                                        ->ifTrue(
                                            function ($v) {
                                                return !is_null($v) && !is_numeric($v);
                                            }
                                        )
                                        ->thenInvalid('host timeout must be numeric')
                                    ->end()
                                ->end()
                            ->end()
                        ->end()
                    ->end()
                ->end()
            ->end()
        ;

        return $node;
    }

    /**
     * Configure the "aequasi_cache.session" section
     *
     * @return ArrayNodeDefinition
     */
    private function addSessionSupportSection()
    {
        $tree = new TreeBuilder();
        $node = $tree->root('session');

        $node
            ->addDefaultsIfNotSet()
            ->children()
                ->booleanNode('enabled')
                    ->defaultFalse()
                ->end()
                ->scalarNode('instance')->end()
                ->scalarNode('prefix')
                    ->defaultValue("session_")
                ->end()
                ->scalarNode('ttl')->end()
            ->end()
        ;

        return $node;
    }

    /**
     * Configure the "aequasi_cache.doctrine" section
     *
     * @return ArrayNodeDefinition
     */
    private function addDoctrineSection()
    {
        $tree = new TreeBuilder();
        $node = $tree->root('doctrine');

        $node
            ->addDefaultsIfNotSet()
            ->children()
                ->booleanNode('enabled')
                    ->defaultFalse()
                    ->isRequired()
                ->end()
            ->end()
        ;

        $types = array('metadata', 'result', 'query');
        foreach ($types as $type) {
            $node->children()
                ->arrayNode($type)
                ->canBeUnset()
                ->children()
                ->scalarNode('instance')
                ->end()
                ->arrayNode('entity_managers')
                ->defaultValue(array())
                ->beforeNormalization()
                ->ifString()
                ->then(
                    function ($v) {
                        return (array) $v;
                    }
                )
                ->end()
                ->prototype('scalar')
                ->end()
                ->end()
                ->arrayNode('document_managers')
                ->defaultValue(array())
                ->beforeNormalization()
                ->ifString()
                ->then(
                    function ($v) {
                        return (array) $v;
                    }
                )
                ->end()
                ->prototype('scalar')
                ->end()
                ->end()
                ->end()
                ->end();
        }

        return $node;
    }

    /**
     * Configure the "aequasi_cache.router" section
     *
     * @return ArrayNodeDefinition
     */
    private function addRouterSection()
    {
        $tree = new TreeBuilder();
        $node = $tree->root('router');

        $node->addDefaultsIfNotSet()
            ->children()
            ->booleanNode('enabled')
            ->defaultFalse()
            ->end()
            ->scalarNode('instance')
            ->defaultNull()
            ->end()
            ->end();

        return $node;
    }
}
