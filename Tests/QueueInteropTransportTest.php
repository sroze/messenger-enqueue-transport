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

use Enqueue\AmqpTools\DelayStrategyAware;
use Enqueue\AmqpTools\RabbitMqDelayPluginDelayStrategy;
use Enqueue\MessengerAdapter\QueueInteropTransport;
use Interop\Queue\Consumer;
use Interop\Queue\Queue;
use Interop\Queue\Topic;
use PHPUnit\Framework\TestCase;
use Interop\Queue\Context;
use Interop\Queue\Producer;
use Interop\Queue\Message;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\Transport\SenderInterface;
use Symfony\Component\Messenger\Transport\Serialization\SerializerInterface;
use Enqueue\MessengerAdapter\ContextManager;
use Enqueue\MessengerAdapter\EnvelopeItem\TransportConfiguration;
use Interop\Queue\Exception\Exception;

class QueueInteropTransportTest extends TestCase
{
    public function testInterfaces()
    {
        $transport = $this->getTransport();

        $this->assertInstanceOf(SenderInterface::class, $transport);
    }

    public function testSendAndEnsuresTheInfrastructureExistsWithDebug()
    {
        $topic = 'topic';
        $queue = 'queue';
        $message = new \stdClass();
        $message->foo = 'bar';
        $envelope = new Envelope($message);

        $psrMessageProphecy = $this->prophesize(Message::class);
        $psrMessage = $psrMessageProphecy->reveal();
        $topicProphecy = $this->prophesize(Topic::class);
        $psrTopic = $topicProphecy->reveal();

        $producerProphecy = $this->prophesize(PsrProducerWithDelay::class);
        $producerProphecy->setDeliveryDelay(100)->shouldBeCalled();
        $producerProphecy->setDelayStrategy(new RabbitMqDelayPluginDelayStrategy())->shouldBeCalled();
        $producerProphecy->setPriority(100)->shouldBeCalled();
        $producerProphecy->setTimeToLive(100)->shouldBeCalled();
        $producerProphecy->send($psrTopic, $psrMessage)->shouldBeCalled();

        $contextProphecy = $this->prophesize(Context::class);
        $contextProphecy->createTopic($topic)->shouldBeCalled()->willReturn($psrTopic);
        $contextProphecy->createProducer()->shouldBeCalled()->willReturn($producerProphecy->reveal());
        $contextProphecy->createMessage('foo', array(), array())->shouldBeCalled()->willReturn($psrMessage);

        $contextManagerProphecy = $this->prophesize(ContextManager::class);
        $contextManagerProphecy->context()->shouldBeCalled()->willReturn($contextProphecy->reveal());
        $contextManagerProphecy->ensureExists(array('topic' => $topic, 'queue' => $queue))->shouldBeCalled();

        $encoderProphecy = $this->prophesize(SerializerInterface::class);
        $encoderProphecy->encode($envelope)->shouldBeCalled()->willReturn(array('body' => 'foo'));

        $transport = $this->getTransport(
            null,
            $encoderProphecy->reveal(),
            $contextManagerProphecy->reveal(),
            array(
                'topic' => array('name' => $topic),
                'queue' => array('name' => $queue),
                'deliveryDelay' => 100,
                'delayStrategy' => RabbitMqDelayPluginDelayStrategy::class,
                'priority' => 100,
                'timeToLive' => 100,
                'receiveTimeout' => 100,
            ),
            true
        );

        $transport->send($envelope);
    }

    public function testSendWithoutDebugWillNotVerifyTheInfrastructureForPerformanceReasons()
    {
        $topic = 'topic';
        $queue = 'queue';
        $message = new \stdClass();
        $message->foo = 'bar';
        $envelope = new Envelope($message);

        $psrMessageProphecy = $this->prophesize(Message::class);
        $psrMessage = $psrMessageProphecy->reveal();
        $topicProphecy = $this->prophesize(Topic::class);
        $psrTopic = $topicProphecy->reveal();

        $producerProphecy = $this->prophesize(Producer::class);
        $producerProphecy->send($psrTopic, $psrMessage)->shouldBeCalled();

        $contextProphecy = $this->prophesize(Context::class);
        $contextProphecy->createTopic($topic)->shouldBeCalled()->willReturn($psrTopic);
        $contextProphecy->createProducer()->shouldBeCalled()->willReturn($producerProphecy->reveal());
        $contextProphecy->createMessage('foo', array(), array())->shouldBeCalled()->willReturn($psrMessage);

        $contextManagerProphecy = $this->prophesize(ContextManager::class);
        $contextManagerProphecy->context()->shouldBeCalled()->willReturn($contextProphecy->reveal());

        $encoderProphecy = $this->prophesize(SerializerInterface::class);
        $encoderProphecy->encode($envelope)->shouldBeCalled()->willReturn(array('body' => 'foo'));

        $transport = $this->getTransport(
            null,
            $encoderProphecy->reveal(),
            $contextManagerProphecy->reveal(),
            array(
                'topic' => array('name' => $topic),
                'queue' => array('name' => $queue),
            ),
            false
        );

        $transport->send($envelope);
    }

    public function testSendMessageOnSpecificTopic()
    {
        $topic = 'topic';
        $queue = 'queue';
        $specificTopic = 'specific-topic';
        $message = new \stdClass();
        $message->foo = 'bar';
        $envelope = (new Envelope($message))->with(new TransportConfiguration(array('topic' => $specificTopic)));

        $psrMessageProphecy = $this->prophesize(Message::class);
        $psrMessage = $psrMessageProphecy->reveal();
        $topicProphecy = $this->prophesize(Topic::class);
        $psrTopic = $topicProphecy->reveal();

        $producerProphecy = $this->prophesize(Producer::class);
        $producerProphecy->send($psrTopic, $psrMessage)->shouldBeCalled();

        $contextProphecy = $this->prophesize(Context::class);
        $contextProphecy->createTopic($specificTopic)->shouldBeCalled()->willReturn($psrTopic);
        $contextProphecy->createProducer()->shouldBeCalled()->willReturn($producerProphecy->reveal());
        $contextProphecy->createMessage('foo', array(), array())->shouldBeCalled()->willReturn($psrMessage);

        $contextManagerProphecy = $this->prophesize(ContextManager::class);
        $contextManagerProphecy->context()->shouldBeCalled()->willReturn($contextProphecy->reveal());
        $contextManagerProphecy->ensureExists(array('topic' => $specificTopic, 'queue' => $queue))->shouldBeCalled();

        $encoderProphecy = $this->prophesize(SerializerInterface::class);
        $encoderProphecy->encode($envelope)->shouldBeCalled()->willReturn(array('body' => 'foo'));

        $transport = $this->getTransport(
            null,
            $encoderProphecy->reveal(),
            $contextManagerProphecy->reveal(),
            array(
                'topic' => array('name' => $topic),
                'queue' => array('name' => $queue),
            ),
            true
        );

        $transport->send($envelope);
    }

    /**
     * @expectedException \Enqueue\MessengerAdapter\Exception\SendingMessageFailedException
     */
    public function testThrow()
    {
        $topic = 'topic';
        $queue = 'queue';
        $message = new \stdClass();
        $message->foo = 'bar';
        $envelope = new Envelope($message);

        $psrMessageProphecy = $this->prophesize(Message::class);
        $psrMessage = $psrMessageProphecy->reveal();
        $topicProphecy = $this->prophesize(Topic::class);
        $psrTopic = $topicProphecy->reveal();

        $exception = new Exception();

        $producerProphecy = $this->prophesize(Producer::class);
        $producerProphecy->send($psrTopic, $psrMessage)->shouldBeCalled()->willThrow($exception);

        $contextProphecy = $this->prophesize(Context::class);
        $contextProphecy->createTopic($topic)->shouldBeCalled()->willReturn($psrTopic);
        $contextProphecy->createProducer()->shouldBeCalled()->willReturn($producerProphecy->reveal());
        $contextProphecy->createMessage('foo', array(), array())->shouldBeCalled()->willReturn($psrMessage);

        $contextManagerProphecy = $this->prophesize(ContextManager::class);
        $contextManagerProphecy->context()->shouldBeCalled()->willReturn($contextProphecy->reveal());
        $contextManagerProphecy->recoverException($exception, array('topic' => $topic, 'queue' => $queue))->shouldBeCalled()->willReturn(false);

        $encoderProphecy = $this->prophesize(SerializerInterface::class);
        $encoderProphecy->encode($envelope)->shouldBeCalled()->willReturn(array('body' => 'foo'));

        $transport = $this->getTransport(
            null,
            $encoderProphecy->reveal(),
            $contextManagerProphecy->reveal(),
            array(
                'topic' => array('name' => $topic),
                'queue' => array('name' => $queue),
            ),
            false
        );

        $transport->send($envelope);
    }

    public function testNullHandler()
    {
        $psrConsumerProphecy = $this->prophesize(Consumer::class);
        $psrConsumerProphecy->receive(0)->shouldBeCalled()->willReturn(null);

        $psrQueueProphecy = $this->prophesize(Queue::class);
        $psrQueue = $psrQueueProphecy->reveal();

        $contextProphecy = $this->prophesize(Context::class);
        $contextProphecy->createQueue('messages')->shouldBeCalled()->willReturn($psrQueue);
        $contextProphecy->createConsumer($psrQueue)->shouldBeCalled()->willReturn($psrConsumerProphecy->reveal());

        $contextManagerProphecy = $this->prophesize(ContextManager::class);
        $contextManagerProphecy->context()->shouldBeCalled()->willReturn($contextProphecy->reveal());

        $transport = $this->getTransport(null, null, $contextManagerProphecy->reveal());
        $handlerArgument = 'not-null';
        $handler = function ($argument) use (&$handlerArgument, $transport) {
            $handlerArgument = $argument;
            $transport->stop();
        };
        $transport->receive($handler);
        $this->assertNull($handlerArgument);
    }

    private function getTransport(SerializerInterface $decoder = null, SerializerInterface $encoder = null, ContextManager $contextManager = null, array $options = array(), $debug = false)
    {
        return new QueueInteropTransport(
            $decoder ?: $this->prophesize(SerializerInterface::class)->reveal(),
            $encoder ?: $this->prophesize(SerializerInterface::class)->reveal(),
            $contextManager ?: $this->prophesize(ContextManager::class)->reveal(),
            $options,
            $debug
        );
    }
}

interface PsrProducerWithDelay extends Producer, DelayStrategyAware
{
}
