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
use Enqueue\MessengerAdapter\EnvelopeItem\RepeatMessage;
use Enqueue\MessengerAdapter\Exception\RepeatMessageException;
use Enqueue\MessengerAdapter\QueueInteropTransport;
use Interop\Queue\PsrConsumer;
use Interop\Queue\PsrQueue;
use Interop\Queue\PsrTopic;
use PHPUnit\Framework\TestCase;
use Interop\Queue\PsrContext;
use Interop\Queue\PsrProducer;
use Interop\Queue\PsrDestination;
use Interop\Queue\PsrMessage;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\Transport\SenderInterface;
use Symfony\Component\Messenger\Transport\Serialization\EncoderInterface;
use Enqueue\MessengerAdapter\ContextManager;
use Enqueue\MessengerAdapter\EnvelopeItem\TransportConfiguration;
use Interop\Queue\Exception;
use Symfony\Component\Messenger\Transport\Serialization\DecoderInterface;

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

        $psrMessageProphecy = $this->prophesize(PsrMessage::class);
        $psrMessage = $psrMessageProphecy->reveal();
        $topicProphecy = $this->prophesize(PsrDestination::class);
        $psrTopic = $topicProphecy->reveal();

        $producerProphecy = $this->prophesize(PsrProducerWithDelay::class);
        $producerProphecy->setDeliveryDelay(100)->shouldBeCalled();
        $producerProphecy->setDelayStrategy(new RabbitMqDelayPluginDelayStrategy())->shouldBeCalled();
        $producerProphecy->setPriority(100)->shouldBeCalled();
        $producerProphecy->setTimeToLive(100)->shouldBeCalled();
        $producerProphecy->send($psrTopic, $psrMessage)->shouldBeCalled();

        $psrContextProphecy = $this->prophesize(PsrContext::class);
        $psrContextProphecy->createTopic($topic)->shouldBeCalled()->willReturn($psrTopic);
        $psrContextProphecy->createProducer()->shouldBeCalled()->willReturn($producerProphecy->reveal());
        $psrContextProphecy->createMessage('foo', array(), array())->shouldBeCalled()->willReturn($psrMessage);

        $contextManagerProphecy = $this->prophesize(ContextManager::class);
        $contextManagerProphecy->psrContext()->shouldBeCalled()->willReturn($psrContextProphecy->reveal());
        $contextManagerProphecy->ensureExists(array('topic' => $topic, 'queue' => $queue))->shouldBeCalled();

        $encoderProphecy = $this->prophesize(EncoderInterface::class);
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

        $psrMessageProphecy = $this->prophesize(PsrMessage::class);
        $psrMessage = $psrMessageProphecy->reveal();
        $topicProphecy = $this->prophesize(PsrDestination::class);
        $psrTopic = $topicProphecy->reveal();

        $producerProphecy = $this->prophesize(PsrProducer::class);
        $producerProphecy->send($psrTopic, $psrMessage)->shouldBeCalled();

        $psrContextProphecy = $this->prophesize(PsrContext::class);
        $psrContextProphecy->createTopic($topic)->shouldBeCalled()->willReturn($psrTopic);
        $psrContextProphecy->createProducer()->shouldBeCalled()->willReturn($producerProphecy->reveal());
        $psrContextProphecy->createMessage('foo', array(), array())->shouldBeCalled()->willReturn($psrMessage);

        $contextManagerProphecy = $this->prophesize(ContextManager::class);
        $contextManagerProphecy->psrContext()->shouldBeCalled()->willReturn($psrContextProphecy->reveal());

        $encoderProphecy = $this->prophesize(EncoderInterface::class);
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

        $psrMessageProphecy = $this->prophesize(PsrMessage::class);
        $psrMessage = $psrMessageProphecy->reveal();
        $topicProphecy = $this->prophesize(PsrDestination::class);
        $psrTopic = $topicProphecy->reveal();

        $producerProphecy = $this->prophesize(PsrProducer::class);
        $producerProphecy->send($psrTopic, $psrMessage)->shouldBeCalled();

        $psrContextProphecy = $this->prophesize(PsrContext::class);
        $psrContextProphecy->createTopic($specificTopic)->shouldBeCalled()->willReturn($psrTopic);
        $psrContextProphecy->createProducer()->shouldBeCalled()->willReturn($producerProphecy->reveal());
        $psrContextProphecy->createMessage('foo', array(), array())->shouldBeCalled()->willReturn($psrMessage);

        $contextManagerProphecy = $this->prophesize(ContextManager::class);
        $contextManagerProphecy->psrContext()->shouldBeCalled()->willReturn($psrContextProphecy->reveal());
        $contextManagerProphecy->ensureExists(array('topic' => $specificTopic, 'queue' => $queue))->shouldBeCalled();

        $encoderProphecy = $this->prophesize(EncoderInterface::class);
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

        $psrMessageProphecy = $this->prophesize(PsrMessage::class);
        $psrMessage = $psrMessageProphecy->reveal();
        $topicProphecy = $this->prophesize(PsrDestination::class);
        $psrTopic = $topicProphecy->reveal();

        $exception = new Exception();

        $producerProphecy = $this->prophesize(PsrProducer::class);
        $producerProphecy->send($psrTopic, $psrMessage)->shouldBeCalled()->willThrow($exception);

        $psrContextProphecy = $this->prophesize(PsrContext::class);
        $psrContextProphecy->createTopic($topic)->shouldBeCalled()->willReturn($psrTopic);
        $psrContextProphecy->createProducer()->shouldBeCalled()->willReturn($producerProphecy->reveal());
        $psrContextProphecy->createMessage('foo', array(), array())->shouldBeCalled()->willReturn($psrMessage);

        $contextManagerProphecy = $this->prophesize(ContextManager::class);
        $contextManagerProphecy->psrContext()->shouldBeCalled()->willReturn($psrContextProphecy->reveal());
        $contextManagerProphecy->recoverException($exception, array('topic' => $topic, 'queue' => $queue))->shouldBeCalled()->willReturn(false);

        $encoderProphecy = $this->prophesize(EncoderInterface::class);
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
        $psrConsumerProphecy = $this->prophesize(PsrConsumer::class);
        $psrConsumerProphecy->receive(0)->shouldBeCalled()->willReturn(null);

        $psrQueueProphecy = $this->prophesize(PsrQueue::class);
        $psrQueue = $psrQueueProphecy->reveal();

        $psrContextProphecy = $this->prophesize(PsrContext::class);
        $psrContextProphecy->createQueue('messages')->shouldBeCalled()->willReturn($psrQueue);
        $psrContextProphecy->createConsumer($psrQueue)->shouldBeCalled()->willReturn($psrConsumerProphecy->reveal());

        $contextManagerProphecy = $this->prophesize(ContextManager::class);
        $contextManagerProphecy->psrContext()->shouldBeCalled()->willReturn($psrContextProphecy->reveal());

        $transport = $this->getTransport(null, null, $contextManagerProphecy->reveal());
        $handlerArgument = 'not-null';
        $handler = function ($argument) use (&$handlerArgument, $transport) {
            $handlerArgument = $argument;
            $transport->stop();
        };
        $transport->receive($handler);
        $this->assertNull($handlerArgument);
    }

    public function testRepeatMessage()
    {
        $psrMessage = $this->createMock(PsrMessage::class);

        $psrConsumer = $this->createMock(PsrConsumer::class);
        $psrConsumer->expects($this->once())
            ->method('receive')
            ->with($this->equalTo(0))
            ->willReturn($psrMessage);
        $psrConsumer->expects($this->once())
            ->method('reject')
            ->with($this->equalTo($psrMessage));

        $psrProducer = $this->createMock(PsrProducer::class);
        $psrProducer->expects($this->once())
            ->method('setDeliveryDelay')
            ->with($this->equalTo(1000));
        $psrProducer->expects($this->once())
            ->method('send');

        $psrQueue = $this->createMock(PsrQueue::class);
        $psrTopic = $this->createMock(PsrTopic::class);

        $psrContext = $this->createMock(PsrContext::class);
        $psrContext->expects($this->once())
            ->method('createQueue')
            ->willReturn($psrQueue);
        $psrContext->expects($this->once())
            ->method('createTopic')
            ->willReturn($psrTopic);
        $psrContext->expects($this->once())
            ->method('createConsumer')
            ->with($psrQueue)
            ->willReturn($psrConsumer);
        $psrContext->expects($this->once())
            ->method('createProducer')
            ->willReturn($psrProducer);
        $psrContext->expects($this->once())
            ->method('createMessage')
            ->willReturn($psrMessage);

        $contextManager = $this->createMock(ContextManager::class);
        $contextManager->expects($this->exactly(2))
            ->method('psrContext')
            ->willReturn($psrContext);

        $envelope = new Envelope($psrMessage);
        $envelopeRepeat = $envelope->with(new RepeatMessage(1, 3));

        $decoder = $this->createMock(DecoderInterface::class);
        $decoder->expects($this->once())
            ->method('decode')
            ->willReturn($envelope);

        $encoder = $this->createMock(EncoderInterface::class);
        $encoder->expects($this->once())
            ->method('encode')
            ->with($this->equalTo($envelopeRepeat))
            ->willReturn(array('body' => null));

        $transport = $this->getTransport($decoder, $encoder, $contextManager);
        $transport->receive(function ($envelope) use ($transport) {
            $transport->stop();
            throw new RepeatMessageException();
        });
    }

    public function testRepeatMessageNotRepeatable()
    {
        $psrMessage = $this->createMock(PsrMessage::class);

        $psrConsumer = $this->createMock(PsrConsumer::class);
        $psrConsumer->expects($this->once())
            ->method('receive')
            ->with($this->equalTo(0))
            ->willReturn($psrMessage);
        $psrConsumer->expects($this->once())
            ->method('reject')
            ->with($this->equalTo($psrMessage));

        $psrQueue = $this->createMock(PsrQueue::class);

        $psrContext = $this->createMock(PsrContext::class);
        $psrContext->expects($this->once())
            ->method('createQueue')
            ->willReturn($psrQueue);
        $psrContext->expects($this->once())
            ->method('createConsumer')
            ->with($psrQueue)
            ->willReturn($psrConsumer);

        $contextManager = $this->createMock(ContextManager::class);
        $contextManager->expects($this->once())
            ->method('psrContext')
            ->willReturn($psrContext);

        $envelope = new Envelope($psrMessage, array(new RepeatMessage(1, 3, 3)));

        $decoder = $this->createMock(DecoderInterface::class);
        $decoder->expects($this->once())
            ->method('decode')
            ->willReturn($envelope);

        $transport = $this->getTransport($decoder, null, $contextManager);
        $transport->receive(function ($envelope) use ($transport) {
            $transport->stop();
            throw new RepeatMessageException();
        });
    }

    private function getTransport(
        DecoderInterface $decoder = null,
        EncoderInterface $encoder = null,
        ContextManager $contextManager = null,
        array $options = array(),
        $debug = false
    ) {
        return new QueueInteropTransport(
            $decoder ?: $this->prophesize(DecoderInterface::class)->reveal(),
            $encoder ?: $this->prophesize(EncoderInterface::class)->reveal(),
            $contextManager ?: $this->prophesize(ContextManager::class)->reveal(),
            $options,
            $debug
        );
    }
}

interface PsrProducerWithDelay extends PsrProducer, DelayStrategyAware
{
}
