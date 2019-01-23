<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Enqueue\MessengerAdapter\Tests;

use PHPUnit\Framework\TestCase;
use Enqueue\MessengerAdapter\Bundle\DependencyInjection\EnqueueAdapterExtension;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Extension\ExtensionInterface;
use Symfony\Component\DependencyInjection\Extension\ConfigurationExtensionInterface;
use Prophecy\Argument;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class EnqueueBridgeExtensionTest extends TestCase
{
    private $extension;

    public function setUp()
    {
        $this->extension = new EnqueueAdapterExtension();
    }

    public function testConstruct()
    {
        $this->extension = new EnqueueAdapterExtension();
        $this->assertInstanceOf(ExtensionInterface::class, $this->extension);
        $this->assertInstanceOf(ConfigurationExtensionInterface::class, $this->extension);
    }

    /**
     * @expectedException \LogicException
     * @expectedExceptionMessage The default Messenger serializer cannot be enabled as the Serializer support is not available. Try enabling it or running "composer require symfony/serializer-pack".
     */
    public function testLoadWithoutDefaultConfiguration()
    {
        $containerBuilderProphecy = $this->prophesize(ContainerBuilder::class);
        $containerBuilderProphecy->fileExists(Argument::any())->willReturn(false);
        $containerBuilderProphecy->hasDefinition('messenger.transport.serializer')->shouldBeCalled()->willReturn(false);
        $containerBuilderProphecy->setDefinition('enqueue.messenger_transport.factory', Argument::allOf(Argument::type(Definition::class), Argument::that(function (Definition $definition) {
            return $definition->hasTag('messenger.transport_factory');
        })))->shouldBeCalled();

        $this->extension->load(array(), $containerBuilderProphecy->reveal());
    }

    public function testLoadWithDefaultSymfonySerializer()
    {
        $containerBuilderProphecy = $this->prophesize(ContainerBuilder::class);
        $containerBuilderProphecy->fileExists(Argument::any())->willReturn(false);
        $containerBuilderProphecy->hasDefinition('messenger.transport.serializer')->shouldBeCalled()->willReturn(true);
        $containerBuilderProphecy->setDefinition('enqueue.messenger_transport.factory', Argument::allOf(Argument::type(Definition::class), Argument::that(function (Definition $definition) {
            return $definition->hasTag('messenger.transport_factory');
        })))->shouldBeCalled();

        $this->extension->load(array(), $containerBuilderProphecy->reveal());
    }
}
