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
use Cache\CacheBundle\Cache\DoctrineTaggingCachePool;
use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;
use Symfony\Component\DependencyInjection\Reference;

/**
 * Class DoctrineSupportCompilerPass.
 *
 * @author Aaron Scherer <aequasi@gmail.com>
 * @author Tobias Nyholm <tobias.nyholm@gmail.com>
 */
class DoctrineSupportCompilerPass extends BaseCompilerPass
{
    /**
     * @throws \Exception
     *
     * @return void
     */
    protected function prepare()
    {
        // If disabled, continue
        if (!$this->container->hasParameter('cache.doctrine')) {
            return;
        }

        if (!$this->hasDoctrine()) {
            throw new \Exception(
                'Not able to find any doctrine caches to implement. Ensure you have Doctrine ORM or ODM'
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

                // Doctrine can't talk to a PSR-6 cache, so we need a bridge
                $bridgeServiceId = sprintf('cache.provider.doctrine.%s.bridge', $cacheType);
                $bridgeDef       = $this->container->register($bridgeServiceId, DoctrineCacheBridge::class);
                $bridgeDef->addArgument(new Reference($this->getPoolReferenceForBridge($bridgeServiceId, $cacheData, $config['use_tagging'])))
                    ->setPublic(false);

                foreach ($cacheData[$type] as $manager) {
                    $doctrineDefinitionId =
                        sprintf(
                            'doctrine.%s.%s_%s_cache',
                            ($type === 'entity_managers' ? 'orm' : 'odm'),
                            $manager,
                            $cacheType
                        );

                    // Replace the doctrine entity manager cache with our bridge
                    $this->container->setAlias($doctrineDefinitionId, $bridgeServiceId);
                }
            }
        }
    }

    /**
     * Get a reference string for the PSR-6 cache implementation service to use with doctrine.
     * If we support tagging we use the DoctrineTaggingCachePool.
     *
     * @param string $bridgeServiceId
     * @param array  $cacheData
     * @param bool   $tagging
     *
     * @return string
     */
    public function getPoolReferenceForBridge($bridgeServiceId, $cacheData, $tagging)
    {
        if (!$tagging) {
            return $cacheData['service_id'];
        }

        $taggingServiceId = $bridgeServiceId.'.tagging';
        $taggingDef       = $this->container->register($taggingServiceId, DoctrineTaggingCachePool::class);
        $taggingDef->addArgument(new Reference($cacheData['service_id']))
            ->setPublic(false);

        return $taggingServiceId;
    }

    /**
     * Checks to see if there are ORM's or ODM's.
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
