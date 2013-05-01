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
class SetCommand extends MemcachedAwareCommand
{

	/**
	 *
	 */
	protected function configure()
	{
		$this->setName( 'memcached:set' )
			->setDescription( 'Set a key\'s value to memcached' )
			->addArgument( 'key', InputArgument::REQUIRED, 'What key do you want to set' )
			->addArgument( 'value', InputArgument::REQUIRED, 'What do you want the value to be' )
			->addArgument( 'lifeTime', InputArgument::OPTIONAL, 'How long do you want it to be cached? ( 0 for infinite, Default: 60 seconds  )', 60 );
	}

	/**
	 * @param InputInterface  $input
	 * @param OutputInterface $output
	 *
	 * @return int|null|void
	 */
	protected function execute( InputInterface $input, OutputInterface $output )
	{
		$key = $input->getArgument( 'key' );
		$value = $input->getArgument( 'value' );
		$lifeTime = $input->getArgument( 'lifeTime' );

		$this->getMemcached()->save( $key, $value, $lifeTime );

		$output->writeln( sprintf( '<info>Key: %s</info>', $key ) );
		$output->writeln( sprintf( '<info>Value: %s</info>', $value ) );
		if( $this->getMemcached()->hasError() ) {
			$output->writeln( sprintf( '<error>%s</error>', $this->getMemcached()->getError() ) );
		}
		$output->writeln( "\n" );

		return;
	}
}
