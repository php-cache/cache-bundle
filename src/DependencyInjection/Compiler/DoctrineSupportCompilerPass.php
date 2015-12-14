<?php

/*
 * This file is part of php-cache\cache-bundle package.
 *
 * (c) 2015-2015 Aaron Scherer <aequasi@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Cache\CacheBundle\DependencyInjection\Compiler;

use Cache\Bridge\DoctrineCacheBridge;
use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;

/**
 * Class DoctrineSupportCompilerPass
 *
 * @author Aaron Scherer <aequasi@gmail.com>
 * @author Tobias Nyholm <tobias.nyholm@gmail.com>
 */
class DoctrineSupportCompilerPass extends BaseCompilerPass
{
    /**
     * @return void
     * @throws \Exception
     */
    protected function prepare()
    {
        // If disabled, continue
        if (!$this->container->hasParameter('cache.doctrine')) {
            return;
        }

        if (!$this->hasDoctrine()) {
            throw new \Exception(
                "Not able to find any doctrine caches to implement. Ensure you have Doctrine ORM or ODM"
            );
        }

        if (!class_exists('Cache\Bridge\DoctrineCacheBridge')) {
            throw new \Exception(
                'You need the DoctrineBridge to be able to cache queries, results and metadata. Please run "composer require cache/psr-6-doctrine-bridge" to install the missing dependency.'
            );
        }

        $this->enableDoctrineSupport($this->container->getParameter('cache.doctrine'));
    }

    /**
     * Loads the Doctrine configuration.
     *
     * @param array $config A configuration array
     *
     * @throws InvalidConfigurationException
     */
    protected function enableDoctrineSupport(array $config)
    {
        $types = ['entity_managers', 'document_managers'];
        foreach ($config as $cacheType => $cacheData) {
            foreach ($types as $type) {
                if (!isset($cacheData[$type])) {
                    continue;
                }

                $bridgeServiceId = sprintf('cache.provider.doctrine.%s.bridge', $cacheType);
                $bridgeDef = $this->container->register($bridgeServiceId, DoctrineCacheBridge::class);
                $bridgeDef->addArgument(0, new Reference($cacheData['service_id']))
                    ->setPublic(false);

                foreach ($cacheData[$type] as $manager) {
                    $doctrineDefinitionName =
                        sprintf(
                            "doctrine.%s.%s_%s_cache",
                            ($type == 'entity_managers' ? 'orm' : 'odm'),
                            $manager,
                            $cacheType
                        );

                    // Replace the doctrine entity manager cache with our bridge
                    $this->container->setAlias($doctrineDefinitionName, $bridgeServiceId);
                }
            }
        }
    }

    /**
     * Checks to see if there are ORM's or ODM's
     *
     * @return bool
     */
    private function hasDoctrine()
    {
        return
            $this->container->hasAlias('doctrine.orm.entity_manager') ||
            $this->container->hasAlias('doctrine_mongodb.document_manager');
    }
}
