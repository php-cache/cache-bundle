<?php
namespace Aequasi\Bundle\MemcachedBundle\DataCollector;

use Aequasi\Bundle\MemcachedBundle\Cache\LoggingMemcachedInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\DataCollector\DataCollector;
use Symfony\Component\Yaml\Yaml;

/**
 * MemcachedDataCollector
 *
 * Based on Lsw\MemcacheBundle
 */
class MemcachedDataCollector extends DataCollector
{

	/**
	 * @var
	 */
	private $clusters;

	/**
	 * @var array
	 */
	private $options;

	/**
	 * Class constructor
	 */
	public function __construct()
	{
		$this->clusters = array();
		$this->options  = array();
	}

	/**
	 * Add a Memcached object to the collector
	 *
	 * @param string                    $name      Name of the Memcached client
	 * @param array                     $options   Options for Memcached client
	 * @param LoggingMemcachedInterface $memcached Logging Memcached object
	 *
	 * @return void
	 */
	public function addCluster( $name, $options, LoggingMemcachedInterface $memcached )
	{
		$this->clusters[ $name ] = $memcached;
		$this->options[ $name ]  = $options;
	}

	/**
	 * {@inheritdoc}
	 */
	public function collect( Request $request, Response $response, \Exception $exception = null )
	{
		$empty      = array( 'calls' => array(), 'config' => array(), 'options' => array(), 'statistics' => array() );
		$this->data = array( 'clusters' => $empty, 'total' => $empty );
		foreach ( $this->clusters as $name => $memcached ) {
			$calls                                          = $memcached->getLoggedCalls();
			$this->data[ 'clusters' ][ 'calls' ][ $name ]   = $calls;
			$this->data[ 'clusters' ][ 'options' ][ $name ] = $this->options[ $name ];
		}
		$this->data[ 'clusters' ][ 'statistics' ] = $this->calculateStatistics( );
		$this->data[ 'total' ][ 'statistics' ]    = $this->calculateTotalStatistics(
			$this->data[ 'clusters' ][ 'statistics' ]
		);
	}

	/**
	 * {@inheritdoc}
	 */
	public function getName()
	{
		return 'memcached';
	}

	/**
	 * Method returns amount of logged Memcached reads: "get" calls
	 *
	 * @return number
	 */
	public function getStatistics()
	{
		return $this->data[ 'clusters' ][ 'statistics' ];
	}

	/**
	 * Method returns the statistic totals
	 *
	 * @return number
	 */
	public function getTotals()
	{
		return $this->data[ 'total' ][ 'statistics' ];
	}

	/**
	 * Method returns all logged Memcached call objects
	 *
	 * @return mixed
	 */
	public function getCalls()
	{
		return $this->data[ 'clusters' ][ 'calls' ];
	}

	/**
	 * Method returns all Memcached options
	 *
	 * @return mixed
	 */
	public function getOptions()
	{
		return $this->data[ 'clusters' ][ 'options' ];
	}

	/**
	 * @return array
	 */
	private function calculateStatistics( )
	{
		$statistics = array();
		foreach ( $this->data[ 'clusters' ][ 'calls' ] as $name => $calls ) {
			$statistics[ $name ] = array(
				'calls'  => 0,
				'time'   => 0,
				'reads'  => 0,
				'hits'   => 0,
				'misses' => 0,
				'writes' => 0
			);
			foreach ( $calls as $call ) {
				$statistics[ $name ][ 'calls' ] += 1;
				$statistics[ $name ][ 'time' ] += $call->time;
				if ( $call->name == 'get' ) {
					$statistics[ $name ][ 'reads' ] += 1;
					if ( $call->result !== false ) {
						$statistics[ $name ][ 'hits' ] += 1;
					} else {
						$statistics[ $name ][ 'misses' ] += 1;
					}
				} elseif ( $call->name == 'get' ) {
					$statistics[ $name ][ 'writes' ] += 1;
				}
			}
			if ( $statistics[ $name ][ 'reads' ] ) {
				$statistics[ $name ][ 'ratio' ] = 100 * $statistics[ $name ][ 'hits' ] / $statistics[ $name ][ 'reads' ] . '%';
			} else {
				$statistics[ $name ][ 'ratio' ] = 'N/A';
			}
		}

		return $statistics;
	}

	/**
	 * @param $statistics
	 *
	 * @return array
	 */
	private function calculateTotalStatistics( $statistics )
	{
		$totals = array( 'calls' => 0, 'time' => 0, 'reads' => 0, 'hits' => 0, 'misses' => 0, 'writes' => 0 );
		foreach ( $statistics as $name => $values ) {
			foreach ( $totals as $key => $value ) {
				$totals[ $key ] += $statistics[ $name ][ $key ];
			}
		}
		if ( $totals[ 'reads' ] ) {
			$totals[ 'ratio' ] = 100 * $totals[ 'hits' ] / $totals[ 'reads' ] . '%';
		} else {
			$totals[ 'ratio' ] = 'N/A';
		}

		return $totals;
	}
}
