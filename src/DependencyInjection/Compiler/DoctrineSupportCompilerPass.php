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

use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;

/**
 * Class DoctrineSupportCompilerPass
 *
 * @author Aaron Scherer <aequasi@gmail.com>
 */
class DoctrineSupportCompilerPass extends BaseCompilerPass
{
    /**
     * @return mixed|void
     */
    protected function prepare()
    {
        // If there is no active session support, return
        if (!$this->container->hasAlias('doctrine.orm.entity_manager')) {
            return;
        }

        // If the aequasi.cache.session_handler service is loaded set the alias
        if ($this->container->hasParameter($this->getAlias() . '.doctrine')) {
            $this->enableDoctrineSupport($this->container->getParameter($this->getAlias() . '.doctrine'));
        }
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
        $types = array('entity_managers', 'document_managers');
        foreach ($config as $cacheType => $cacheData) {
            foreach ($types as $type) {
                if (!isset($cacheData[$type])) {
                    continue;
                }

                if (!isset($cacheData['instance'])) {
                    throw new InvalidConfigurationException(sprintf(
                        "There was no instance passed. Please specify a instance under the %s type",
                        $cacheType
                    ));
                }
                $cacheDefinitionName = sprintf('%s.instance.%s.bridge', $this->getAlias(), $cacheData['instance']);

                foreach ($cacheData[$type] as $manager) {
                    $doctrineDefinitionName =
                        sprintf(
                            "doctrine.%s.%s_%s_cache",
                            ($type == 'entity_managers' ? 'orm' : 'odm'),
                            $manager,
                            $cacheType
                        );

                    // Replace the doctrine entity manager cache with our bridge
                    $this->container->setAlias($doctrineDefinitionName, $cacheDefinitionName);
                }
            }
        }
    }
}
