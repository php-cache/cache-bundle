<?php

/*
 * This file is part of php-cache\cache-bundle package.
 *
 * (c) 2015-2015 Aaron Scherer <aequasi@gmail.com>, Tobias Nyholm <tobias.nyholm@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Cache\CacheBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

/**
 * @author Tobias Nyholm <tobias.nyholm@gmail.com>
 */
class ServiceInjectorPass implements CompilerPassInterface
{
    /**
     * @throws \Exception
     */
    public function process(ContainerBuilder $container)
    {
        $this->injectAnnotationService($container);
        $this->injectSerializerService($container);
        $this->injectValidationService($container);
    }

    /**
     * @param ContainerBuilder $container
     *
     * @throws \Exception
     */
    private function injectAnnotationService(ContainerBuilder $container)
    {
        // If disabled, continue
        if (!$container->hasParameter('cache.annotation')) {
            return;
        }

        $container
            ->getDefinition('annotations.cached_reader')
            ->replaceArgument(1, new Reference('cache.service.annotation'));
    }

    /**
     * @param ContainerBuilder $container
     *
     * @throws \Exception
     */
    private function injectSerializerService(ContainerBuilder $container)
    {
        // If disabled, continue
        if (!$container->hasParameter('cache.serializer')) {
            return;
        }

        $container->getDefinition('serializer.mapping.class_metadata_factory')
            ->replaceArgument(1, new Reference('cache.service.serializer'));
    }
    /**
     * @param ContainerBuilder $container
     *
     * @throws \Exception
     */
    private function injectValidationService(ContainerBuilder $container)
    {
        // If disabled, continue
        if (!$container->hasParameter('cache.validation')) {
            return;
        }

        $container->getDefinition('validator.builder')
            ->addMethodCall('setMetadataCache', [new Reference('cache.service.serializer')]);
    }
}
