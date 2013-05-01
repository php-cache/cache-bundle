<?php
/**
 * @author    Aaron Scherer <aequasi@gmail.com>
 * @date 2013
 * @license   http://www.apache.org/licenses/LICENSE-2.0.html Apache License, Version 2.0
 */
namespace Aequasi\Bundle\MemcachedBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

use Aequasi\Bundle\MemcachedBundle\Service\MemcachedService;

/**
 * GetCommand
 *
 * Grabs the given key out of cache
 */
class MemcachedAwareCommand extends ContainerAwareCommand
{

	/**
	 * @var MemcachedService
	 */
	protected $memcached;

	protected function initialize( InputInterface $input, OutputInterface $output )
	{
		$this->setMemcached( $this->getContainer()->get( 'memcached' ) );
		parent::initialize( $input, $output );
	}

	/**
	 * @param MemcachedService $memcached
	 *
	 * @return $this
	 */
	public function setMemcached( MemcachedService $memcached )
	{
		$this->memcached = $memcached;

		return $this;
	}

	/**
	 * @return MemcachedService
	 */
	public function getMemcached()
	{
		return $this->memcached;
	}
}
