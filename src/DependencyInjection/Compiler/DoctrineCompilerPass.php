<?php

/*
 * This file is part of php-cache\cache-bundle package.
 *
 * (c) 2015 Aaron Scherer <aequasi@gmail.com>, Tobias Nyholm <tobias.nyholm@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Cache\CacheBundle\DependencyInjection\Compiler;

use Cache\Bridge\Doctrine\DoctrineCacheBridge;
use Cache\CacheBundle\Factory\DoctrineBridgeFactory;
use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

/**
 * Add the doctrine bridge around the PSR-6 cache services.
 *
 * @author Aaron Scherer <aequasi@gmail.com>
 * @author Tobias Nyholm <tobias.nyholm@gmail.com>
 */
class DoctrineCompilerPass implements CompilerPassInterface
{
    /**
     * @type ContainerBuilder
     */
    private $container;

    /**
     * @throws \Exception
     *
     * @return void
     */
    public function process(ContainerBuilder $container)
    {
        $this->container = $container;

        // If disabled, continue
        if (!$this->container->hasParameter('cache.doctrine')) {
            return;
        }

        if (!$this->hasDoctrine()) {
            throw new \Exception(
                'Not able to find any doctrine caches to implement. Ensure you have Doctrine ORM or ODM'
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
        // For each ['metadata' => [], 'result' => [], 'query' => []]
        foreach ($config as $cacheType => $typeConfig) {
            foreach ($types as $type) {
                if (!isset($typeConfig[$type])) {
                    continue;
                }

                // Copy the tagging setting to the $typeConfig
                $typeConfig['use_tagging'] = $config['use_tagging'];

                // Doctrine can't talk to a PSR-6 cache, so we need a bridge
                $bridgeServiceId = sprintf('cache.service.doctrine.%s.%s.bridge', $cacheType, $type);
                $this->container->register($bridgeServiceId, DoctrineCacheBridge::class)
                    ->setPublic(false)
                    ->setFactory([DoctrineBridgeFactory::class, 'get'])
                    ->addArgument(new Reference($typeConfig['service_id']))
                    ->addArgument($typeConfig)
                    ->addArgument(['doctrine', $cacheType]);

                foreach ($typeConfig[$type] as $manager) {
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
