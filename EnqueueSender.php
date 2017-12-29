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
use Interop\Amqp\AmqpQueue;
use Interop\Amqp\AmqpTopic;
use Interop\Amqp\Impl\AmqpBind;
use Interop\Amqp\Impl\AmqpMessage;
use Symfony\Component\Message\Transport\SenderInterface;
use Symfony\Component\Message\Transport\Serialization\EncoderInterface;

/**
 * Symfony Message sender to bridge Php-Enqueue producers.
 *
 * @author Samuel Roze <samuel.roze@gmail.com>
 */
class EnqueueSender implements SenderInterface
{
    /**
     * @var EncoderInterface
     */
    private $messageEncoder;

    /**
     * @var AmqpContext
     */
    private $amqpContext;

    /**
     * @var string
     */
    private $queueName;

    /**
     * @var string
     */
    private $topicName;

    public function __construct(EncoderInterface $messageEncoder, AmqpContext $amqpContext, string $queueName = null, string $topicName = null)
    {
        $this->messageEncoder = $messageEncoder;
        $this->amqpContext = $amqpContext;

        $this->queueName = $queueName ?: 'messages';
        $this->topicName = $topicName ?: $this->queueName;
    }

    /**
     * {@inheritdoc}
     */
    public function send($message)
    {
        $encodedMessage = $this->messageEncoder->encode($message);

        $queue = $this->amqpContext->createQueue($this->queueName);
        $queue->addFlag(AmqpQueue::FLAG_DURABLE);

        $topic = $this->amqpContext->createTopic($this->queueName);
        $topic->setType(AmqpTopic::TYPE_FANOUT);
        $topic->addFlag(AmqpTopic::FLAG_DURABLE);

        $this->amqpContext->declareTopic($topic);
        $this->amqpContext->declareQueue($queue);
        $this->amqpContext->bind(new AmqpBind($topic, $queue, $queue->getQueueName()));

        $producer = $this->amqpContext->createProducer();
        $producer->send($queue, new AmqpMessage(
            $encodedMessage['body'],
            $encodedMessage['properties'] ?? [],
            $encodedMessage['headers'] ?? []
        ));
    }
}
