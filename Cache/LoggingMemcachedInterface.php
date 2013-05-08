<?php
/**
 * @author    Aaron Scherer <aequasi@gmail.com>
 * @date 2013
 * @license   http://www.apache.org/licenses/LICENSE-2.0.html Apache License, Version 2.0
 */
namespace Aequasi\Bundle\MemcachedBundle\Cache;

/**
 * Interface to allow for DataCollector to retrieve logged calls
 */
interface LoggingMemcachedInterface
{
	/**
	 * Get the logged calls for this Memcached object
	 *
	 * @return array Array of all of the calls made to the Memcached object
	 */
	public function getLoggedCalls();

}