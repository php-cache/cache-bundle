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
class DeleteCommand extends MemcachedAwareCommand
{

	protected function configure()
	{
		$this->setName( 'memcached:delete' )
			->setDescription( "Delete a key from memcached" )
			->addArgument( 'key', InputArgument::REQUIRED, 'What key do you want to delete' );
	}

	protected function execute( InputInterface $input, OutputInterface $output )
	{
		$key = $input->getArgument( 'key' );
		$this->getMemcached()->delete( $key );

		$output->writeln( sprintf( '<info>Key: %s</info>', $key ) );
		if( $this->getMemcached()->hasError() ) {
			$output->writeln( sprintf( '<error>%s</error>', $this->getMemcached()->getError() ) );
		}
		$output->writeln( "\n" );
	}
}
