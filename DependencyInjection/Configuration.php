<?php
/**
 * @author    Aaron Scherer <aequasi@gmail.com>
 * @date      2013
 * @license   http://www.apache.org/licenses/LICENSE-2.0.html Apache License, Version 2.0
 */
namespace Aequasi\Bundle\MemcachedBundle\DependencyInjection;

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
 * @package Aequasi\Bundle\MemcachedBundle\DependencyInjection
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
		$rootNode = $treeBuilder->root( 'memcached' );

		$rootNode
			->children()
				->booleanNode( 'enabled' )
					->info( "Enabled or disables this service. Default: True" )
					->defaultTrue()
				->end()
				->append( $this->getClustersNode() )
				->append($this->addSessionSupportSection())
				->append($this->addDoctrineSection())
			->end()
		;

		return $treeBuilder;
	}

	/**
	 * Configure the 'memcached.keyMap` section
	 *
	 * @return ArrayNodeDefinition
	 */
	private function getKeymapNode()
	{
		$treeBuilder = new TreeBuilder();
		$node = $treeBuilder->root( 'keyMap' );

		$node
			->info( "Settings for creating a key map in a database" )
			->children()
				->addDefaultsIfNotSet()
				->children()
					->booleanNode( 'enabled' )
						->info( "Enable or Disable storing keys and their lifetimes to the database. Default: False" )
						->defaultFalse()
					->end()
					->scalarNode( 'connection' )
						->info( "Doctrine Connection Name" )
						->defaultValue( "" )
					->end()
				->end()
			->end()
			;

		return $node;
	}

	/**
	 * @return \Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition|\Symfony\Component\Config\Definition\Builder\NodeDefinition
	 */
	private function getClustersNode( )
	{
		$treeBuilder = new TreeBuilder();
		$node = $treeBuilder->root( 'clusters' );

		$node
			->children()
				->arrayNode( 'clusters' )
					->requiresAtLeastOneElement()
					->addDefaultChildrenIfNoneSet()
					->useAttributeAsKey( 'name' )
					->prototype( 'array' )
						->children()
							->scalarNode('persistent_id')
								->defaultNull()
								->info('Specify to enable persistent connections. All clients with the same ID share connections.')
							->end()
							->arrayNode('hosts')
								->requiresAtLeastOneElement()
								->prototype('array')
									->scalarNode( 'host' )
										->cannotBeEmpty()
										->defaultValue( 'localhost' )
									->end()
									->scalarNode( 'port' )
										->cannotBeEmpty()
										->defaultValue( 11211 )
										->validate()
										->ifTrue( function( $v ) { return !is_numeric( $v ); } )
											->thenInvalid( "Host port must be numeric")
										->end()
										->scalarNode('weight')
											->defaultValue(0)
											->validate()
											->ifTrue(function ($v) { return !is_numeric($v); })
												->thenInvalid('host weight must be numeric')
											->end()
										->end()
									->end()
								->end()
							->end()
							->append( $this->getKeymapNode() )
							->append( $this->getOptionsNode() )
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
				->scalarNode('prefix')->end()
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
	/**
	 * @return \Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition|\Symfony\Component\Config\Definition\Builder\NodeDefinition
	 */
	private function getOptionsNode()
	{
		$treeBuilder = new TreeBuilder();
		$node = $treeBuilder->root( 'options' );

		$node
			->addDefaultsIfNotSet()
			->children()
				->booleanNode( 'compression' )
					->info( 'Enables or disables payload compression. When enabled, item values longer than a certain threshold (currently 100 bytes) will be compressed during storage and decompressed during retrieval transparently. Default: True' )
					->defaultTrue()
				->end()
				->integerNode( 'serializer' )
					->info( 'Specifies the serializer to use for serializing non-scalar values. The valid serializers are Memcached::SERIALIZER_PHP, Memcached::SERIALIZER_JSON, Memcached::SERIALIZER_JSON_ARRAY or Memcached::SERIALIZER_IGBINARY. The latter is supported only when memcached is configured with --enable-memcached-igbinary option and the igbinary extension is loaded. Default: Memcached::SERIALIZER_PHP' )
					->defaultValue( Memcached::SERIALIZER_PHP )
					->validate()
					->ifNotInArray( array( Memcached::SERIALIZER_PHP, Memcached::SERIALIZER_IGBINARY, Memcached::SERIALIZER_JSON, Memcached::SERIALIZER_JSON_ARRAY ) )
						->thenInvalid( 'Invalid serializer type "%s"' )
					->end()
				->end()
				->scalarNode( 'prefix_key' )
					->info( 'This can be used to create a "domain" for your item keys. The value specified here will be prefixed to each of the keys. It cannot be longer than 128 characters and will reduce the maximum available key size. The prefix is applied only to the item keys, not to the server keys. Default: "" ' )
					->defaultValue( "" )
				->end()
				->integerNode( 'hash' )
					->info( "Specifies the hashing algorithm used for the item keys. The valid values are supplied via Memcached::HASH_* constants (can be viewed at http://php.net/manual/en/memcached.constants.php). Each hash algorithm has its advantages and its disadvantages. Go with the default if you don't know or don't care. Default: Memcached::HASH_DEFAULT" )
					->defaultValue( Memcached::HASH_DEFAULT )
					->validate()
						->ifNotInArray( array(
							Memcached::HASH_CRC, Memcached::HASH_DEFAULT, Memcached::HASH_FNV1_32,
							Memcached::HASH_FNV1_64, Memcached::HASH_FNV1A_32, Memcached::HASH_FNV1A_64,
							Memcached::HASH_HSIEH, Memcached::HASH_MD5, Memcached::HASH_MURMUR
						) )
						->thenInvalid( 'Invalid hash type "%s"' )
					->end()
				->end()
				->integerNode( 'distribution' )
					->info( "Specifies the method of distributing item keys to the servers. Currently supported methods are modulo and consistent hashing. Consistent hashing delivers better distribution and allows servers to be added to the cluster with minimal cache losses. Default: Memcached::DISTRIBUTION_MODULA" )
					->defaultValue( Memcached::DISTRIBUTION_MODULA )
					->validate()
						->ifNotInArray( array( Memcached::DISTRIBUTION_MODULA, Memcached::DISTRIBUTION_CONSISTENT ) )
						->thenInvalid( 'Invalid distribution type "%s"' )
					->end()
				->end()
				->booleanNode( 'libketama_compatible' )
					->info( 'Enables or disables compatibility with libketama-like behavior. When enabled, the item key hashing algorithm is set to MD5 and distribution is set to be weighted consistent hashing distribution. This is useful because other libketama-based clients (Python, Ruby, etc.) with the same server configuration will be able to access the keys transparently. It is highly recommended to enable this option if you want to use consistent hashing, and it may be enabled by default in future releases of Memcached. Default: True')
					->defaultTrue()
				->end()
				->booleanNode( 'buffer_writes' )
					->info( 'Enables or disables buffered I/O. Enabling buffered I/O causes storage commands to "buffer" instead of being sent. Any action that retrieves data causes this buffer to be sent to the remote connection. Quitting the connection or closing down the connection will also cause the buffered data to be pushed to the remote connection. Default: False' )
					->defaultFalse()
				->end()
				->booleanNode( 'binary_protocol' )
					->info( 'Enable the use of the binary protocol. Please note that you cannot toggle this option on an open connection. Default: False' )
					->defaultFalse()
				->end()
				->booleanNode( 'no_block' )
					->info( 'Enables or disables asynchronous I/O. This is the fastest transport available for storage functions. Default: False' )
					->defaultFalse()
				->end()
				->booleanNode( 'tcp_no_delay' )
					->info( 'Enables or disables the no-delay feature for connecting sockets (may be faster in some environments). Default: False' )
					->defaultFalse()
				->end()
				->integerNode( 'connect_timeout' )
					->info( 'In non-blocking mode this set the value of the timeout during socket connection, in milliseconds. Default: 1000' )
					->defaultValue( 1000 )
				->end()
				->integerNode( 'retry_timeout' )
					->info( 'The amount of time, in seconds, to wait until retrying a failed connection attempt. Default: 5' )
					->defaultValue( 5 )
				->end()
				->integerNode( 'send_timeout' )
					->info( 'Socket sending timeout, in microseconds. In cases where you cannot use non-blocking I/O this will allow you to still have timeouts on the sending of data. Default: 0' )
					->defaultValue( 0 )
				->end()
				->integerNode( 'recv_timeout' )
					->info( 'Socket reading timeout, in microseconds. In cases where you cannot use non-blocking I/O this will allow you to still have timeouts on the reading of data. Default: 0' )
					->defaultValue( 0 )
				->end()
				->integerNode( 'poll_timeout' )
					->info( 'Timeout for connection polling, in milliseconds. Default: 1000' )
					->defaultValue( 1000 )
				->end()
				->integerNode( 'server_failure_limit' )
					->info( 'Specifies the failure limit for server connection attempts. The server will be removed after this many continuous connection failures. Default: 3' )
					->defaultValue( 3 )
				->end()
			->end()
		;

		return $node;
	}
}
