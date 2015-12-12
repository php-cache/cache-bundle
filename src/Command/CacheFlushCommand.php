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

use Cache\Taggable\TaggablePoolInterface;
use Psr\Cache\CacheItemPoolInterface;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputArgument;

/**
 * Class CacheFlushCommand.
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
        $this->addArgument('type', InputArgument::REQUIRED, 'Which type of cache do you want to clear?');
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $validTypes = ['session', 'routing', 'doctrine', 'all'];
        $type = $input->getArgument('type');
        if (!in_array($type, $validTypes)) {
            $output->writeln(sprintf('Type "%s" does not exist. Valid type are: %s', $type, implode(',', $validTypes)));
        }

        if ($type === 'all') {
            foreach (['session', 'routing', 'doctrine'] as $type) {
                $this->clearCacheForType($type);
            }
        } else {
            $this->clearCacheForType($type);
        }
    }

    /**
     * Clear the cache for a type.
     *
     * @param string $type
     */
    private function clearCacheForType($type)
    {
        $serviceId = $this->getContainer()->getParameter(sprintf('cache.%s%.service_id', $type));

        /** @var CacheItemPoolInterface $service */
        $service = $this->getContainer()->get($serviceId);
        if ($service instanceof TaggablePoolInterface) {
            $service->clear([$type]);
        } else {
            $service->clear();
        }
    }
}
