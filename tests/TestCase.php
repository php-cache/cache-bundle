<?php

/**
 * @author    Aaron Scherer <aequasi@gmail.com>
 * @date      2013
 * @license   http://www.apache.org/licenses/LICENSE-2.0.html Apache License, Version 2.0
 */

namespace Aequasi\Bundle\CacheBundle\Tests;

use Aequasi\Bundle\CacheBundle\DependencyInjection\AequasiCacheExtension;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBag;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;
use Symfony\Component\Config\FileLocator;

/**
 * Class TestCase
 *
 * @author Aaron Scherer <aequasi@gmail.com>
 */
class TestCase extends \PHPUnit_Framework_TestCase
{

    /**
     * @param ContainerBuilder $container
     * @param string           $file
     */
    protected function loadFromFile(ContainerBuilder $container, $file)
    {
        $loader = new YamlFileLoader($container, new FileLocator(__DIR__ . '/Fixtures'));
        $loader->load($file . '.yml');
    }

    /**
     * @param array $data
     *
     * @return ContainerBuilder
     */
    protected function createContainer(array $data = array())
    {
        return new ContainerBuilder(new ParameterBag(array_merge(
            array(
                'kernel.bundles'     => array('FrameworkBundle' => 'Symfony\\Bundle\\FrameworkBundle\\FrameworkBundle'),
                'kernel.cache_dir'   => __DIR__,
                'kernel.debug'       => false,
                'kernel.environment' => 'test',
                'kernel.name'        => 'kernel',
                'kernel.root_dir'    => __DIR__,
            ),
            $data
        )));
    }

    /**
     * @param string $file
     * @param array  $data
     *
     * @return ContainerBuilder
     */
    protected function createContainerFromFile($file, $data = array())
    {
        $container = $this->createContainer($data);
        $container->registerExtension(new AequasiCacheExtension());
        $this->loadFromFile($container, $file);

        $container->getCompilerPassConfig()
            ->setOptimizationPasses(array());
        $container->getCompilerPassConfig()
            ->setRemovingPasses(array());
        $container->compile();

        return $container;
    }
}
