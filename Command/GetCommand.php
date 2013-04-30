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

/**
 * GetCommand
 *
 * Grabs the given key out of cache
 */
class GetCommand extends ContainerAwareCommand
{

	protected function configure()
	{
		$this->setName( 'memcached:get' )
			->setDescription( 'Get a key\'s value from memcached' )
			->addArgument( 'key', InputArgument::REQUIRED, 'What key do you want to get' );
	}

	protected function execute( InputInterface $input, OutputInterface $output )
	{
		$key = $input->getArgument( 'key' );
		$value = $this->getValueFromKey( $key );

		$output->writeln( sprintf( '<info>Key: %s', $key ) );
		$output->writeln( sprintf( '<info>Value: %s', $value ) );
		$output->writeln( "\n" );
	}

	protected function getValueFromKey( $key )
	{
		return $this->getContainer()->get( 'memcached' )->get( $key );
	}
}
