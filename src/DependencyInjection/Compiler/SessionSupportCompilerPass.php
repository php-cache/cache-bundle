<?php

declare(strict_types=1);

namespace Cache\CacheBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

final class SessionSupportCompilerPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container): void
    {
        if (!$container->hasParameter('cache.session')) {
            return;
        }

        if (!$container->hasDefinition('session.factory')) {
            throw new \LogicException('Session cache support requires the Symfony session service.');
        }

        $container->setAlias('session.handler', 'cache.service.session');
    }
}
