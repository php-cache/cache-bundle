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
use Memcached;

/**
 * This class contains the configuration information for the bundle
 *
 * This information is solely responsible for how the different configuration
 * sections are normalized, and merged.
 *
 * @package Aequasi\Bundle\CacheBundle\DependencyInjection
 *
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
	public function  __construct($debug)
	{
		$this->debug = (Boolean) $debug;
	}

	/**
	 * Generates the configuration tree builder.
	 *
	 * @return \Symfony\Component\Config\Definition\Builder\TreeBuilder The tree builder
	 */
	public function getConfigTreeBuilder()
	{
		$treeBuilder = new TreeBuilder();
		$rootNode = $treeBuilder->root( 'cache' );

		$rootNode
			->children()
				->append( $this->getClustersNode() )
				->append($this->addSessionSupportSection())
				->append($this->addDoctrineSection())
			->end()
		;

		return $treeBuilder;
	}

	/**
	 * @return \Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition|\Symfony\Component\Config\Definition\Builder\NodeDefinition
	 */
	private function getClustersNode( )
	{
		$treeBuilder = new TreeBuilder();
		$node = $treeBuilder->root( 'instances' );

		$node
			->requiresAtLeastOneElement()
			->addDefaultChildrenIfNoneSet( 'default' )
			->useAttributeAsKey( 'name' )
			->prototype( 'array' )
				->children()
					->enumNode('type')
						->values(array('redis', 'file', 'memcached', 'apc'))
					->end()
					->scalarNode('persistent_id')
						->defaultNull()
						->info('For Memcached: Specify to enable persistent connections. All clients with the same ID share connections.')
					->end()
					->booleanNode('persistent')
						->defaultNull()
						->info("For Redis: Specify if you want persistent connections")
					->end()
					->booleanNode('enabled')
						->info("Enabled or disables this service. Default: True")
						->defaultTrue()
					->end()
					->scalarNode('auth_password')
						->info("For Redis: Authorization info")
					->end()
					->arrayNode('options')->info("Options for Redis and Memcached")->end()
					->arrayNode('hosts')
						->prototype('array')
							->children()
								->scalarNode('host')
									->cannotBeEmpty()
									->defaultValue('localhost')
								->end()
								->scalarNode('port')
									->cannotBeEmpty()
									->validate()
									->ifTrue(function ($v) { return !is_numeric($v); })
										->thenInvalid("Host port must be numeric")
									->end()
								->end()
								->scalarNode('weight')
									->info("For Memcached: Weight for given host.")
									->defaultValue(0)
									->validate()
									->ifTrue(function ($v) { return !is_numeric($v); })
										->thenInvalid('host weight must be numeric')
									->end()
								->end()
								->scalarNode('timeout')
									->info("For Redis: Timeout for the given host.")
									->defaultValue(0)
									->validate()
									->ifTrue(function ($v) { return !is_numeric($v); })
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
	 * Configure the "memcached.session" section
	 *
	 * @return ArrayNodeDefinition
	 */
	private function addSessionSupportSection()
	{
		$tree = new TreeBuilder();
		$node = $tree->root('session');

		$node
			->children()
				->scalarNode('cluster')->isRequired()->end()
				->scalarNode('prefix')->defaultValue("session_")->end()
				->scalarNode('ttl')->end()
			->end()
		->end();

		return $node;
	}


  /**
   * Configure the "memcached.doctrine" section
   *
   * @return ArrayNodeDefinition
   */
  private function addDoctrineSection()
  {
      $tree = new TreeBuilder();
      $node = $tree->root('doctrine');

      foreach (array('metadata', 'result', 'query') as $type) {
          $node->children()
              ->arrayNode($type)
                  ->canBeUnset()
                  ->children()
                      ->scalarNode('cluster')->isRequired()->end()
                      ->scalarNode('prefix')->defaultValue('')->end()
                  ->end()
                  ->fixXmlConfig('entity_manager')
                  ->children()
                      ->arrayNode('entity_managers')
                          ->defaultValue(array())
                          ->beforeNormalization()->ifString()->then(function($v) { return (array) $v; })->end()
                          ->prototype('scalar')->end()
                      ->end()
                  ->end()
                  ->fixXmlConfig('document_manager')
                  ->children()
                      ->arrayNode('document_managers')
                          ->defaultValue(array())
                          ->beforeNormalization()->ifString()->then(function($v) { return (array) $v; })->end()
                          ->prototype('scalar')->end()
                      ->end()
                  ->end()
              ->end()
          ->end();
      }

      return $node;
  }
}
