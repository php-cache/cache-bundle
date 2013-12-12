<?php
/**
 * @author    Aaron Scherer <aequasi@gmail.com>
 * @date      2013
 * @license   http://www.apache.org/licenses/LICENSE-2.0.html Apache License, Version 2.0
 */
namespace Aequasi\Bundle\CacheBundle\DependencyInjection\Compiler;

use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;

/**
 * DoctrineCompilerPass is a compiler pass to set the doctrine caches.
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
                    throw new InvalidConfigurationException(sprintf("There was no instance passed. Please specify a instance under the %s type", $cacheType));
                }
                $cacheDefinitionName = sprintf('%s.instance.%s', $this->getAlias(), $cacheData['instance']);

                foreach ($cacheData[$type] as $manager) {
                    $doctrineDefinitionName = sprintf("doctrine.%s.%s_%s_cache", ($type == 'entity_managers' ? 'orm' : 'odm'), $manager, $cacheType);
                    $this->container->setAlias($doctrineDefinitionName, $cacheDefinitionName);
                }
            }
        }
    }
}