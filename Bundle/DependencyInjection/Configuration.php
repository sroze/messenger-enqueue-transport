<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Enqueue\MessengerAdapter\Bundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

class Configuration implements ConfigurationInterface
{
    /**
     * {@inheritdoc}
     */
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder();

        $treeBuilder
            ->root('enqueue_adapter')
                ->canBeDisabled()
                ->children()
                    ->scalarNode('queue')->isRequired()->end()
                    ->scalarNode('topic')->defaultNull()->end()
                    ->scalarNode('deliveryDelay')->defaultNull()->end()
                    ->scalarNode('timeToLive')->defaultNull()->end()
                    ->scalarNode('priority')->defaultNull()->end()
                ->end()
            ->end()
        ;

        return $treeBuilder;
    }
}
