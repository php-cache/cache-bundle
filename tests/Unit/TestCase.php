<?php

/*
 * This file is part of php-cache\cache-bundle package.
 *
 * (c) 2015 Aaron Scherer <aequasi@gmail.com>, Tobias Nyholm <tobias.nyholm@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Cache\CacheBundle\Tests\Unit;

use Cache\CacheBundle\DependencyInjection\CacheExtension;
use PHPUnit\Framework\TestCase as BaseTestCase;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBag;

/**
 * Class TestCase.
 *
 * @author Aaron Scherer <aequasi@gmail.com>
 */
class TestCase extends BaseTestCase
{
    /**
     * @param ContainerBuilder $container
     * @param string           $file
     */
    protected function loadFromFile(ContainerBuilder $container, $file)
    {
        $loader = new YamlFileLoader($container, new FileLocator(__DIR__.'/Fixtures'));
        $loader->load($file.'.yml');
    }

    /**
     * @param array $data
     *
     * @return ContainerBuilder
     */
    protected function createContainer(array $data = [])
    {
        return new ContainerBuilder(new ParameterBag(array_merge(
            [
                'kernel.bundles'     => ['FrameworkBundle' => 'Symfony\\Bundle\\FrameworkBundle\\FrameworkBundle'],
                'kernel.cache_dir'   => __DIR__,
                'kernel.debug'       => false,
                'kernel.environment' => 'test',
                'kernel.name'        => 'kernel',
                'kernel.root_dir'    => __DIR__,
            ],
            $data
        )));
    }

    /**
     * @param string $file
     * @param array  $data
     *
     * @return ContainerBuilder
     */
    protected function createContainerFromFile($file, $data = [])
    {
        $container = $this->createContainer($data);
        $container->registerExtension(new CacheExtension());
        $this->loadFromFile($container, $file);

        $container->getCompilerPassConfig()
            ->setOptimizationPasses([]);
        $container->getCompilerPassConfig()
            ->setRemovingPasses([]);
        $container->compile();

        return $container;
    }
}
