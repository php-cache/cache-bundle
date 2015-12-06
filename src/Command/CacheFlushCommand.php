<?php

/*
 * This file is part of php-cache\cache-bundle package.
 *
 * (c) 2015-2015 Aaron Scherer <aequasi@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Cache\CacheBundle\Command;

use Psr\Cache\CacheItemPoolInterface;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputArgument;

/**
 * Class CacheFlushCommand
 *
 * @author Aaron Scherer <aequasi@gmail.com>
 */
class CacheFlushCommand extends ContainerAwareCommand
{

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this->setName('cache:flush');
        $this->setDescription('Flushes the given cache');
        $this->addArgument('instance', InputArgument::REQUIRED, 'Which instance do you want to clean?');
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $serviceName = sprintf('aequasi_cache.instance.%s', $input->getArgument('instance'));

        /** @var CacheItemPoolInterface $service */
        $service = $this->getContainer()->get($serviceName);
        $service->clear();
    }
}
