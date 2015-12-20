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
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

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
        $validTypes = ['session', 'router', 'doctrine'];
        $type       = $input->getArgument('type');
        if ($type === 'all') {
            foreach ($validTypes as $type) {
                $this->clearCacheForType($type);
            }

            return;
        }

        // If not "all", verify that $type is valid
        if (!in_array($type, $validTypes)) {
            $output->writeln(sprintf('Type "%s" does not exist. Valid type are: %s', $type, implode(',', $validTypes)));

            return;
        }

        $this->clearCacheForType($type);
    }

    /**
     * Clear the cache for a type.
     *
     * @param string $type
     */
    private function clearCacheForType($type)
    {
        if (!$this->getContainer()->hasParameter(sprintf('cache.%s', $type))) {
            return;
        }

        $config = $this->getContainer()->getParameter(sprintf('cache.%s', $type));

        /** @type CacheItemPoolInterface $service */
        $service = $this->getContainer()->get($config['service_id']);
        if ($service instanceof TaggablePoolInterface) {
            $service->clear([$type]);
        } else {
            $service->clear();
        }
    }
}
