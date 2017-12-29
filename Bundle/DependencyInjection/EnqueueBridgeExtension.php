<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Sam\Symfony\Bridge\EnqueueMessage\Bundle\DependencyInjection;

use Sam\Symfony\Bridge\EnqueueMessage\EnqueueReceiver;
use Sam\Symfony\Bridge\EnqueueMessage\EnqueueSender;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;

class EnqueueBridgeExtension extends Extension
{
    /**
     * {@inheritdoc}
     */
    public function load(array $configs, ContainerBuilder $container)
    {
        $config = $this->processConfiguration(new Configuration(), $configs);
        if (!$config['enabled']) {
            return;
        }

        $receiverDefinition = new Definition(EnqueueReceiver::class, [
            new Reference('message.transport.default_decoder'),
            new Reference('enqueue.transport.default.context'),
            $config['queue'],
        ]);
        $receiverDefinition->setPublic(true);

        $senderDefinition = new Definition(EnqueueSender::class, [
            new Reference('message.transport.default_encoder'),
            new Reference('enqueue.transport.default.context'),
            $config['queue'],
            $config['topic'] ?: $config['queue']
        ]);
        $senderDefinition->setPublic(true);

        $container->setDefinitions([
            'enqueue_bridge.receiver' => $receiverDefinition,
            'enqueue_bridge.sender' => $senderDefinition,
        ]);
    }
}
