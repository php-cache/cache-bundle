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
use aequasi_cache;

/**
 * Class Configuration
 *
 * @package Aequasi\Bundle\CacheBundle\DependencyInjection
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
    public function  __construct( $debug )
    {
        $this->debug = (Boolean)$debug;
    }

    /**
     * Generates the configuration tree builder.
     *
     * @return \Symfony\Component\Config\Definition\Builder\TreeBuilder The tree builder
     */
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder();
        $rootNode    = $treeBuilder->root( 'cache' );

        $rootNode
            ->children()
                ->append( $this->getClustersNode() )
                ->append( $this->addSessionSupportSection() )
                ->append( $this->addDoctrineSection() )
                ->append( $this->addRouterSection() )
            ->end();

        return $treeBuilder;
    }

    /**
     * @return \Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition|\Symfony\Component\Config\Definition\Builder\NodeDefinition
     */
    private function getClustersNode()
    {
        $treeBuilder = new TreeBuilder();
        $node        = $treeBuilder->root( 'instances' );

        $node
            ->requiresAtLeastOneElement()
            ->addDefaultChildrenIfNoneSet( 'default' )
            ->useAttributeAsKey( 'name' )
            ->prototype( 'array' )
                ->children()
                    ->enumNode( 'type' )
                        ->values( array( 'redis', 'file', 'memcached', 'apc' ) )
                    ->end()
                    ->scalarNode( 'id' )
                        ->defaultNull()
                    ->end()
                    ->scalarNode( 'namespace' )
                        ->defaultNull()
                        ->info( "Namespace for doctrine keys." )
                    ->end()
                    ->booleanNode( 'persistent' )
                        ->defaultNull()
                        ->info( "For Redis and Memcached: Specify if you want persistent connections" )
                    ->end()
                    ->scalarNode( 'auth_password' )
                        ->info( "For Redis: Authorization info" )
                    ->end()
                    ->scalarNode( 'directory' )
                        ->info( "For File System and PHP File: Directory to store cache." )
                        ->defaultNull()
                    ->end()
                    ->scalarNode( 'extension' )
                        ->info( "For File System and PHP File: Extension to use." )
                        ->defaultNull()
                    ->end()
                    ->arrayNode( 'options' )
                        ->info( "Options for Redis and Memcached" )
                    ->end()
                    ->arrayNode( 'hosts' )
                        ->prototype( 'array' )
                            ->children()
                                ->scalarNode( 'host' )
                                    ->defaultNull()
                                ->end()
                                ->scalarNode( 'port' )
                                    ->defaultNull()
                                    ->validate()
                                        ->ifTrue( function ( $v ) { return !is_null( $v ) && !is_numeric( $v ); } )
                                        ->thenInvalid( "Host port must be numeric" )
                                    ->end()
                                ->end()
                                ->scalarNode( 'weight' )
                                    ->info( "For Memcached: Weight for given host." )
                                    ->defaultNull()
                                    ->validate()
                                        ->ifTrue( function ( $v ) { return !is_null( $v ) && !is_numeric( $v ); } )
                                        ->thenInvalid( 'host weight must be numeric' )
                                    ->end()
                                ->end()
                                ->scalarNode( 'timeout' )
                                    ->info( "For Redis and Memcache: Timeout for the given host." )
                                    ->defaultNull()
                                    ->validate()
                                        ->ifTrue( function ( $v ) { return !is_null( $v ) && !is_numeric( $v ); } )
                                        ->thenInvalid( 'host timeout must be numeric' )
                                    ->end()
                                ->end()
                            ->end()
                        ->end()
                    ->end()
                ->end()
            ->end();

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
        $node = $tree->root( 'session' );

        $node
            ->addDefaultsIfNotSet()
            ->children()
                ->scalarNode( 'instance' )->isRequired()->end()
                ->scalarNode( 'prefix' )->defaultValue( "session_" )->end()
                ->scalarNode( 'ttl' )->end()
            ->end();

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
        $node = $tree->root( 'doctrine' );

        $types = array('metadata', 'result', 'query');
        foreach ($types as $type) {
            $node
                ->children()
                    ->arrayNode( $type )
                        ->canBeUnset()
                        ->children()
                            ->scalarNode( 'instance' )->isRequired()->end()
                            ->arrayNode( 'entity_managers' )
                                ->defaultValue( array() )
                                ->beforeNormalization()
                                    ->ifString()
                                    ->then( function ( $v ) { return (array)$v; } )
                                ->end()
                                ->prototype( 'scalar' )->end()
                            ->end()
                            ->arrayNode( 'document_managers' )
                                ->defaultValue( array() )
                                ->beforeNormalization()
                                    ->ifString()
                                    ->then( function ( $v ) { return (array)$v; } )
                                ->end()
                                ->prototype( 'scalar' )->end()
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
        $node = $tree->root( 'router' );

        $node
            ->addDefaultsIfNotSet()
            ->children()
                ->scalarNode( 'instance' )->defaultNull()->end()
                ->booleanNode( 'enabled' )->defaultFalse()->end()
            ->end();

        return $node;
    }
}
