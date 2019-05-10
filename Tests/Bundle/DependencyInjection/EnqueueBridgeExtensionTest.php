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

    public function testLoad()
    {
        $containerBuilderProphecy = $this->prophesize(ContainerBuilder::class);
        $containerBuilderProphecy->fileExists(Argument::any())->willReturn(false);
        if (method_exists(ContainerBuilder::class, 'removeBindings')) {
            $containerBuilderProphecy->removeBindings('enqueue.messenger_transport.factory')->shouldBeCalledOnce();
        }
        $containerBuilderProphecy->setDefinition('enqueue.messenger_transport.factory', Argument::allOf(Argument::type(Definition::class), Argument::that(function (Definition $definition) {
            return $definition->hasTag('messenger.transport_factory');
        })))->shouldBeCalled();

        $this->extension->load(array(), $containerBuilderProphecy->reveal());
    }
}
