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

use Enqueue\AmqpTools\RabbitMqDelayPluginDelayStrategy;
use Enqueue\MessengerAdapter\QueueInteropTransport;
use Enqueue\MessengerAdapter\QueueInteropTransportFactory;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Enqueue\MessengerAdapter\AmqpContextManager;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Messenger\Transport\Serialization\EncoderInterface;
use Symfony\Component\Messenger\Transport\Serialization\DecoderInterface;
use Interop\Queue\PsrContext;

class QueueInteropTransportFactoryTest extends TestCase
{
    public function testSupports()
    {
        $factory = $this->getFactory();

        $this->assertTrue($factory->supports('enqueue://something', array()));
        $this->assertFalse($factory->supports('amqp://something', array()));
    }

    public function testCreatesTransport()
    {
        $queuePsrContext = $this->prophesize(PsrContext::class)->reveal();
        $decoder = $this->prophesize(DecoderInterface::class);
        $encoder = $this->prophesize(EncoderInterface::class);

        $container = $this->prophesize(ContainerInterface::class);
        $container->has('enqueue.transport.default.context')->willReturn(true);
        $container->get('enqueue.transport.default.context')->willReturn($queuePsrContext);

        $factory = $this->getFactory($decoder->reveal(), $encoder->reveal(), $container->reveal());
        $dsn = 'enqueue://default';

        $expectedTransport = new QueueInteropTransport($decoder->reveal(), $encoder->reveal(), new AmqpContextManager($queuePsrContext), array(), true);
        $this->assertEquals($expectedTransport, $factory->createTransport($dsn, array()));

        // Ensure BC for Symfony beta 4.1
        $this->assertEquals($expectedTransport, $factory->createSender($dsn, array()));
        $this->assertEquals($expectedTransport, $factory->createReceiver($dsn, array()));
    }

    public function testDnsParsing()
    {
        $queuePsrContext = $this->prophesize(PsrContext::class)->reveal();
        $decoder = $this->prophesize(DecoderInterface::class);
        $encoder = $this->prophesize(EncoderInterface::class);

        $container = $this->prophesize(ContainerInterface::class);
        $container->has('enqueue.transport.default.context')->willReturn(true);
        $container->get('enqueue.transport.default.context')->willReturn($queuePsrContext);

        $factory = $this->getFactory($decoder->reveal(), $encoder->reveal(), $container->reveal());
        $dsn = 'enqueue://default?queue[name]=test&topic[name]=test&deliveryDelay=100&delayStrategy=Enqueue\AmqpTools\RabbitMqDelayPluginDelayStrategy&timeToLive=100&receiveTimeout=100&priority=100';

        $expectedTransport = new QueueInteropTransport(
            $decoder->reveal(),
            $encoder->reveal(),
            new AmqpContextManager($queuePsrContext),
            array(
                'topic' => array('name' => 'test'),
                'queue' => array('name' => 'test'),
                'deliveryDelay' => 100,
                'delayStrategy' => RabbitMqDelayPluginDelayStrategy::class,
                'priority' => 100,
                'timeToLive' => 100,
                'receiveTimeout' => 100,
            ),
            true
        );

        $this->assertEquals($expectedTransport, $factory->createTransport($dsn, array()));

        // Ensure BC for Symfony beta 4.1
        $this->assertEquals($expectedTransport, $factory->createSender($dsn, array()));
        $this->assertEquals($expectedTransport, $factory->createReceiver($dsn, array()));
    }

    /**
     * @expectedException \RuntimeException
     * @expectedExceptionMessage Can't find Enqueue's transport named "foo": Service "enqueue.transport.foo.context" is not found.
     */
    public function testItThrowsAnExceptionWhenContextDoesNotExist()
    {
        $container = $this->prophesize(ContainerInterface::class);
        $container->has('enqueue.transport.foo.context')->willReturn(false);

        $factory = $this->getFactory();
        $factory->createTransport('enqueue://foo', array());
    }

    private function getFactory(DecoderInterface $decoder = null, EncoderInterface $encoder = null, ContainerInterface $container = null, $debug = true)
    {
        return new QueueInteropTransportFactory(
            $decoder ?: $this->prophesize(DecoderInterface::class)->reveal(),
            $encoder ?: $this->prophesize(EncoderInterface::class)->reveal(),
            $container ?: $this->prophesize(ContainerInterface::class)->reveal(),
            $debug
        );
    }
}
