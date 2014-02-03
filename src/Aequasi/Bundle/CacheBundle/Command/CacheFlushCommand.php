<?php
/**
 * @author    Aaron Scherer
 * @date      12/10/13
 * @license   http://www.apache.org/licenses/LICENSE-2.0.html Apache License, Version 2.0
 */

namespace Aequasi\Bundle\CacheBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Aequasi\Bundle\CacheBundle\Service\CacheService;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputArgument;

/**
 * Class CacheFlushCommand
 *
 * @package Aequasi\Bundle\CacheBundle\Command
 */
class CacheFlushCommand extends ContainerAwareCommand
{

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this->setName( 'cache:flush' );
        $this->setDescription( 'Flushes the given cache' );
        $this->addArgument( 'instance', InputArgument::REQUIRED, 'Which instance do you want to clean?' );
    }

    /**
     * {@inheritdoc}
     */
    protected function execute( InputInterface $input, OutputInterface $output )
    {
        $serviceName = 'aequasi_cache.instance.' . $input->getArgument( 'instance' );

        /** @var CacheService $service */
        $service = $this->getContainer()
                        ->get( $serviceName );
        $service->flushAll();
    }
} 
