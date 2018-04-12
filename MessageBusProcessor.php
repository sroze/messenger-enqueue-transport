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

use Interop\Queue\PsrContext;
use Interop\Queue\PsrMessage;
use Interop\Queue\PsrProcessor;
use Symfony\Component\Messenger\Asynchronous\Transport\ReceivedMessage;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Messenger\Transport\Serialization\DecoderInterface;
use Enqueue\MessengerAdapter\Exception\RejectMessageException;
use Enqueue\MessengerAdapter\Exception\RequeueMessageException;

/**
 * The processor could be used with any queue interop compatible consumer, for example Enqueue's QueueConsumer.
 *
 * @author Max Kotliar <kotlyar.maksim@gmail.com>
 */
class MessageBusProcessor implements PsrProcessor
{
    /**
     * @var MessageBusInterface
     */
    private $bus;

    /**
     * @var DecoderInterface
     */
    private $messageDecoder;

    public function __construct(MessageBusInterface $bus, DecoderInterface $messageDecoder)
    {
        $this->bus = $bus;
        $this->messageDecoder = $messageDecoder;
    }

    public function process(PsrMessage $message, PsrContext $context)
    {
        $busMessage = $this->messageDecoder->decode(array(
            'body' => $message->getBody(),
            'headers' => $message->getHeaders(),
            'properties' => $message->getProperties(),
        ));

        if (!$busMessage instanceof ReceivedMessage) {
            $busMessage = new ReceivedMessage($message);
        }

        try {
            $this->bus->dispatch($busMessage);

            return self::ACK;
        } catch (RejectMessageException $e) {
            return self::REJECT;
        } catch (RequeueMessageException $e) {
            return self::REQUEUE;
        } catch (\Throwable $e) {
            return self::REJECT;
        }
    }
}
