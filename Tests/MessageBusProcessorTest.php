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
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\Stamp\ReceivedStamp;
use Symfony\Component\Messenger\Transport\Serialization\SerializerInterface;
use Symfony\Component\Messenger\MessageBusInterface;
use Interop\Queue\Message;
use Interop\Queue\Context;
use Interop\Queue\Processor;
use Enqueue\MessengerAdapter\MessageBusProcessor;
use Enqueue\MessengerAdapter\Exception\RejectMessageException;
use Enqueue\MessengerAdapter\Exception\RequeueMessageException;
use Prophecy\Argument;

class MessageBusProcessorTest extends TestCase
{
    private function getTestMessage()
    {
        $messageProphecy = $this->prophesize(Message::class);
        $messageProphecy->getBody()->shouldBeCalled()->willReturn('body');
        $messageProphecy->getHeaders()->shouldBeCalled()->willReturn(array('header'));
        $messageProphecy->getProperties()->shouldBeCalled()->willReturn(array('props'));

        return $messageProphecy->reveal();
    }

    public function testProcess()
    {
        $message = $this->getTestMessage();
        $receivedMessage = new ReceivedStamp('test');
        $envelope = new Envelope($receivedMessage);
        $contextProphecy = $this->prophesize(Context::class);
        $busProphecy = $this->prophesize(MessageBusInterface::class);
        $busProphecy->dispatch($envelope)->shouldBeCalled()->willReturn($envelope);
        $decoderProphecy = $this->prophesize(SerializerInterface::class);
        $decoderProphecy->decode(array(
            'body' => 'body',
            'headers' => array('header'),
            'properties' => array('props'),
        ))->shouldBeCalled()->willReturn($envelope);
        $messageBusProcessor = new MessageBusProcessor($busProphecy->reveal(), $decoderProphecy->reveal());
        $this->assertSame(Processor::ACK, $messageBusProcessor->process($message, $contextProphecy->reveal()));
    }

    public function testProcessReject()
    {
        $message = $this->getTestMessage();
        $receivedMessage = new ReceivedStamp('test');
        $envelope = new Envelope($receivedMessage);
        $contextProphecy = $this->prophesize(Context::class);
        $decoderProphecy = $this->prophesize(SerializerInterface::class);
        $decoderProphecy->decode(Argument::any())->shouldBeCalled()->willReturn($envelope);
        $busProphecy = $this->prophesize(MessageBusInterface::class);
        $busProphecy->dispatch($envelope)->shouldBeCalled()->willThrow(new RejectMessageException());
        $messageBusProcessor = new MessageBusProcessor($busProphecy->reveal(), $decoderProphecy->reveal());
        $this->assertSame(Processor::REJECT, $messageBusProcessor->process($message, $contextProphecy->reveal()));
    }

    public function testProcessRequeue()
    {
        $message = $this->getTestMessage();
        $receivedMessage = new ReceivedStamp('test');
        $envelope = new Envelope($receivedMessage);
        $contextProphecy = $this->prophesize(Context::class);
        $decoderProphecy = $this->prophesize(SerializerInterface::class);
        $decoderProphecy->decode(Argument::any())->shouldBeCalled()->willReturn($envelope);
        $busProphecy = $this->prophesize(MessageBusInterface::class);
        $busProphecy->dispatch($envelope)->shouldBeCalled()->willThrow(new RequeueMessageException());
        $messageBusProcessor = new MessageBusProcessor($busProphecy->reveal(), $decoderProphecy->reveal());
        $this->assertSame(Processor::REQUEUE, $messageBusProcessor->process($message, $contextProphecy->reveal()));
    }

    public function testProcessRejectAnyException()
    {
        $message = $this->getTestMessage();
        $receivedMessage = new ReceivedStamp('test');
        $envelope = new Envelope($receivedMessage);
        $contextProphecy = $this->prophesize(Context::class);
        $decoderProphecy = $this->prophesize(SerializerInterface::class);
        $decoderProphecy->decode(Argument::any())->shouldBeCalled()->willReturn($envelope);
        $busProphecy = $this->prophesize(MessageBusInterface::class);
        $busProphecy->dispatch($envelope)->shouldBeCalled()->willThrow(new \InvalidArgumentException());
        $messageBusProcessor = new MessageBusProcessor($busProphecy->reveal(), $decoderProphecy->reveal());
        $this->assertSame(Processor::REJECT, $messageBusProcessor->process($message, $contextProphecy->reveal()));
    }
}
