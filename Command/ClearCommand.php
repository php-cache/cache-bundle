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
use Symfony\Component\DependencyInjection\Exception\ServiceNotFoundException;

/**
 * ClearCommand
 *
 * Flushed the given memcached cluster
 */
class ClearCommand extends ContainerAwareCommand
{

	/**
	 *
	 */
	protected function configure()
	{
		$this
			->setName( 'memcached:clear' )
			->setDescription( 'Invalidate all Memcached items' )
			->addArgument( 'cluster', InputArgument::REQUIRED, 'What cluster do you want to use' );
	}


	/**
	 * @param InputInterface  $input
	 * @param OutputInterface $output
	 *
	 * @return void
	 */
	protected function execute( InputInterface $input, OutputInterface $output )
	{
		$cluster = $input->getArgument( 'cluster' );
		try {
			$memcached = $this->getContainer()->get( 'memcached.' . $cluster );
			$memcached->flush();
			if ( $memcached->hasError() ) {
				$output->writeln( sprintf( '<error>%s</error>', $memcached->getError() ) );
			} else {
				$output->writeln( '<info>OK</info>' );
			}
		} catch( ServiceNotFoundException $e ) {
			$output->writeln( "<error>cluster '{$cluster}' is not found</error>" );
		}
		$output->writeln( "\n" );
	}
}