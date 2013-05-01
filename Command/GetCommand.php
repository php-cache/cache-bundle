<?php
/**
 * @author    Aaron Scherer <aequasi@gmail.com>
 * @date 2013
 * @license   http://www.apache.org/licenses/LICENSE-2.0.html Apache License, Version 2.0
 */
namespace Aequasi\Bundle\MemcachedBundle\Command;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * GetCommand
 *
 * Grabs the given key out of cache
 */
class GetCommand extends MemcachedAwareCommand
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
		$value = $this->getMemcached()->get( $key );

		$output->writeln( sprintf( '<info>Key: %s</info>', $key ) );
		$output->writeln( sprintf( '<info>Value: %s</info>', $value ) );
		if( $this->getMemcached()->hasError() ) {
			$output->writeln( sprintf( '<error>%s</error>', $this->getMemcached()->getError() ) );
		}
		$output->writeln( "\n" );
	}
}
