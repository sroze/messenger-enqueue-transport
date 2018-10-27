<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Enqueue\MessengerAdapter;

use Interop\Queue\Context;
use Interop\Queue\Message;
use Interop\Queue\Processor;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Messenger\Transport\Serialization\SerializerInterface;
use Enqueue\MessengerAdapter\Exception\RejectMessageException;
use Enqueue\MessengerAdapter\Exception\RequeueMessageException;

/**
 * The processor could be used with any queue interop compatible consumer, for example Enqueue's QueueConsumer.
 *
 * @author Max Kotliar <kotlyar.maksim@gmail.com>
 * @author Samuel Roze <samuel.roze@gmail.com>
 */
class MessageBusProcessor implements Processor
{
    private $bus;
    private $messageDecoder;

    public function __construct(MessageBusInterface $bus, SerializerInterface $messageDecoder)
    {
        $this->bus = $bus;
        $this->messageDecoder = $messageDecoder;
    }

    public function process(Message $message, Context $context)
    {
        $busMessage = $this->messageDecoder->decode(array(
            'body' => $message->getBody(),
            'headers' => $message->getHeaders(),
            'properties' => $message->getProperties(),
        ));

        try {
            $this->bus->dispatch($busMessage);

            return Processor::ACK;
        } catch (RejectMessageException $e) {
            return Processor::REJECT;
        } catch (RequeueMessageException $e) {
            return Processor::REQUEUE;
        } catch (\Throwable $e) {
            return Processor::REJECT;
        }
    }
}
