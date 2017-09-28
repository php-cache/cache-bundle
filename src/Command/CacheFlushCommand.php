<?php

/*
 * This file is part of php-cache\cache-bundle package.
 *
 * (c) 2015 Aaron Scherer <aequasi@gmail.com>, Tobias Nyholm <tobias.nyholm@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Cache\CacheBundle\Command;

use Cache\CacheBundle\DataCollector\CacheProxyInterface;
use Cache\Taggable\TaggablePoolInterface;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;

/**
 * Class CacheFlushCommand.
 *
 * @author Aaron Scherer <aequasi@gmail.com>
 */
class CacheFlushCommand extends ContainerAwareCommand
{
    /**
     * @type CacheProxyInterface[]
     */
    private $instances = [];

    /**
     * Make the cache proxies known to the command, so it will be able to flush them.
     *
     * @param string              $name
     * @param CacheProxyInterface $instance
     */
    public function addInstance($serviceName, $instance)
    {
        $this->instances[$serviceName] = $instance;
    }

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this->setName('cache:flush');
        $this->setDescription('Flushes the given cache');
        $this->addArgument('type', InputArgument::OPTIONAL, sprintf('Which type of cache do you want to clear? Valid types are: %s', implode(', ', $this->getValidTypes())));
        $this->addArgument('service', InputArgument::OPTIONAL, 'If using type "provider" you must give a service id for the cache you want to clear.');
        $this->setHelp(<<<'EOD'

Types and their description
all                       Clear all types of caches
annotation                Clear annotation cache
doctrine                  Clear doctrine cache for query, result and metadata
privider cache.acme       Clear all the cache for the provider with service id "cache.acme"
router                    Clear router cache
serializer                Clear serializer cache
session                   Clear session cache. All your logged in users will be logged out.
symfony                   Clear Symfony cache. This is the same as cache:clear
validation                Clear validation cache

EOD
    );
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        if (false === $type = $this->verifyArguments($input, $output)) {
            return 0;
        }

        $builtInTypes = ['annotation', 'doctrine', 'serializer', 'session', 'router', 'validation'];
        if (in_array($type, $builtInTypes)) {
            return $this->clearCacheForBuiltInType($type) ? 0 : 1;
        }

        if ($type === 'symfony') {
            return $this->clearSymfonyCache($output) ? 0 : 1;
        }

        if ($type === 'provider') {
            return $this->clearCacheForProvider($input->getArgument('service')) ? 0 : 1;
        }

        if ($type === 'all') {
            $result = true;
            foreach ($builtInTypes as $builtInType) {
                $output->writeln(" // Clearing cache for built in type <info>'".$builtInType."'</info>");
                $result = $result && $this->clearCacheForBuiltInType($builtInType);
            }
            foreach (array_keys($this->instances) as $instance) {
                $output->writeln(" // Clearing cache for provider <info>'".$instance."'</info>");
                $result = $result && $this->clearCacheForProvider($instance);
            }
            $result = $result && $this->clearSymfonyCache($output);

            return $result ? 0 : 1;
        }
    }

    /**
     * Clear the cache for a type.
     *
     * @param string $type
     *
     * @return bool
     */
    private function clearCacheForBuiltInType($type)
    {
        if (!$this->getContainer()->hasParameter(sprintf('cache.%s', $type))) {
            return true;
        }

        $config = $this->getContainer()->getParameter(sprintf('cache.%s', $type));

        if ($type === 'doctrine') {
            $result = true;
            $result = $result && $this->clearTypedCacheFromService($type, $config['metadata']['service_id']);
            $result = $result && $this->clearTypedCacheFromService($type, $config['result']['service_id']);
            $result = $result && $this->clearTypedCacheFromService($type, $config['query']['service_id']);

            return $result;
        } else {
            return $this->clearTypedCacheFromService($type, $config['service_id']);
        }
    }

    /**
     * @param string $type
     * @param string $serviceId
     *
     * @return bool
     */
    private function clearTypedCacheFromService($type, $serviceId)
    {
        /** @type \Psr\Cache\CacheItemPoolInterface $service */
        $service = $this->getContainer()->get($serviceId);
        if ($service instanceof TaggablePoolInterface) {
            return $service->clearTags([$type]);
        } else {
            return $service->clear();
        }
    }

    /**
     * @param InputInterface  $input
     * @param OutputInterface $output
     *
     * @return string|bool type or false if invalid arguements
     */
    protected function verifyArguments(InputInterface $input, OutputInterface $output)
    {
        $type       = $input->getArgument('type');
        $validTypes = $this->getValidTypes();
        if ($type === null) {
            // ask a question and default $type='all'
            $helper   = $this->getHelper('question');
            $question = new ConfirmationQuestion('Do you want to clear all cache? [N] ', false);

            if (!$helper->ask($input, $output, $question)) {
                return false;
            }

            $type = 'all';
        }

        if (!in_array($type, $validTypes)) {
            $output->writeln(
                sprintf(
                    '<error>Type "%s" does not exist. Valid type are: %s.</error>',
                    $type,
                    implode(', ', $validTypes)
                )
            );

            return false;
        }

        if ($type === 'provider' && !$input->hasArgument('service')) {
            $output->writeln(
                '<error>When using type "provider" you must specify a service id for that provider.</error>'
            );
            $output->writeln('<error>Usage: php app/console cache:flush provider cache.provider.acme</error>');

            return false;
        }

        return $type;
    }

    /**
     * @param string $serviceId
     *
     * @return bool
     */
    protected function clearCacheForProvider($serviceId)
    {
        /** @type \Psr\Cache\CacheItemPoolInterface $service */
        $service = $this->getContainer()->get($serviceId);

        return $service->clear();
    }

    /**
     * @param OutputInterface $output
     *
     * @return bool
     */
    protected function clearSymfonyCache(OutputInterface $output)
    {
        $command   = $this->getApplication()->find('cache:clear');
        $arguments = [
            'command' => 'cache:clear',
        ];

        return $command->run(new ArrayInput($arguments), $output) === 0;
    }

    /**
     * List of valid cache identifiers.
     *
     * @return string[]
     */
    private function getValidTypes()
    {
        return ['all', 'annotation', 'session', 'serializer', 'router', 'doctrine', 'symfony', 'validation', 'provider'];
    }
}
