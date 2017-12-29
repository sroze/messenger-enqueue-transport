<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Sam\Symfony\Bridge\EnqueueMessage;

use Enqueue\AmqpExt\AmqpContext;
use Symfony\Component\Message\Transport\ReceiverInterface;
use Symfony\Component\Message\Transport\Serialization\DecoderInterface;

/**
 * Symfony Message receivers to get messages from php-enqueue consumers.
 *
 * @author Samuel Roze <samuel.roze@gmail.com>
 */
class EnqueueReceiver implements ReceiverInterface
{
    /**
     * @var DecoderInterface
     */
    private $messageDecoder;

    /**
     * @var AmqpContext
     */
    private $amqpContext;

    /**
     * @var string
     */
    private $queueName;

    public function __construct(DecoderInterface $messageDecoder, AmqpContext $amqpContext, string $queueName)
    {
        $this->messageDecoder = $messageDecoder;
        $this->amqpContext = $amqpContext;
        $this->queueName = $queueName;
    }

    /**
     * {@inheritdoc}
     */
    public function receive(): iterable
    {
        $destination = $this->amqpContext->createQueue($this->queueName);
        $consumer = $this->amqpContext->createConsumer($destination);

        while (true) {
            if (null === ($amqpMessage = $consumer->receive(60))) {
                continue;
            }

            try {
                yield $this->messageDecoder->decode([
                    'body' => $amqpMessage->getBody(),
                    'headers' => $amqpMessage->getHeaders(),
                    'properties' => $amqpMessage->getProperties(),
                ]);

                $consumer->acknowledge($amqpMessage);
            } catch (\Exception $e) {
                $consumer->reject($amqpMessage);
            }
        }
    }
}
