<?php

declare(strict_types=1);

/*
 * This file is part of php-cache\cache-bundle package.
 *
 * (c) 2015 Aaron Scherer <aequasi@gmail.com>, Tobias Nyholm <tobias.nyholm@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Cache\CacheBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\Config\Definition\Builder\ScalarNodeDefinition;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

final class Configuration implements ConfigurationInterface
{
    public function getConfigTreeBuilder(): TreeBuilder
    {
        $treeBuilder = new TreeBuilder('cache');
        $rootNode = $treeBuilder->getRootNode();
        if ($rootNode instanceof ArrayNodeDefinition) {
            $children = $rootNode->children();

            $session = $children->arrayNode('session')->canBeEnabled();
            $sessionChildren = $session->children();
            $sessionChildren->append($this->stringNode('service_id')->isRequired());
            $sessionChildren->booleanNode('use_tagging')->defaultTrue();
            $sessionChildren->append($this->stringNode('prefix')->defaultValue('session_'));
            $sessionChildren->integerNode('ttl')->defaultNull();
            $sessionChildren->append($this->stringNode('lock_factory')->defaultValue('lock.factory'));
            $sessionChildren->integerNode('lock_ttl')->min(1)->defaultValue(300);

            $router = $children->arrayNode('router')->canBeEnabled();
            $routerChildren = $router->children();
            $routerChildren->append($this->stringNode('service_id')->isRequired());
            $routerChildren->integerNode('ttl')->defaultValue(604800);
            $routerChildren->booleanNode('use_tagging')->defaultTrue();
            $routerChildren->append($this->stringNode('prefix')->defaultValue(''));

            $logging = $children->arrayNode('logging')->canBeEnabled();
            $logging->children()->append($this->stringNode('logger')->defaultValue('logger'));

            $dataCollector = $children->arrayNode('data_collector')->addDefaultsIfNotSet();
            $dataCollectorChildren = $dataCollector->children();
            $dataCollectorChildren->booleanNode('enabled')->defaultNull();
            $dataCollectorChildren->booleanNode('include_values')->defaultTrue();
        }

        return $treeBuilder;
    }

    private function stringNode(string $name): ScalarNodeDefinition
    {
        $node = new ScalarNodeDefinition($name);
        $node->validate()
            ->ifTrue(static fn (mixed $value): bool => !\is_string($value))
            ->thenInvalid('The value must be a string.')
            ->end();

        return $node;
    }
}
