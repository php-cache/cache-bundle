<?php

declare(strict_types=1);

namespace Cache\CacheBundle;

use Cache\CacheBundle\DependencyInjection\Compiler\CacheTaggingPass;
use Cache\CacheBundle\DependencyInjection\Compiler\DataCollectorCompilerPass;
use Cache\CacheBundle\DependencyInjection\Compiler\LoggerPass;
use Cache\CacheBundle\DependencyInjection\Compiler\SessionSupportCompilerPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

final class CacheBundle extends Bundle
{
    public function build(ContainerBuilder $container): void
    {
        parent::build($container);

        $container->addCompilerPass(new CacheTaggingPass());
        $container->addCompilerPass(new SessionSupportCompilerPass());
        $container->addCompilerPass(new LoggerPass());
        $container->addCompilerPass(new DataCollectorCompilerPass());
    }
}
