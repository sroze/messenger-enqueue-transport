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

use Interop\Amqp\AmqpContext;
use Interop\Amqp\AmqpQueue;
use Interop\Amqp\AmqpTopic;
use Interop\Amqp\Impl\AmqpBind;
use Interop\Queue\Exception;
use Interop\Queue\PsrContext;
use Symfony\Component\Message\Transport\SenderInterface;
use Symfony\Component\Message\Transport\Serialization\EncoderInterface;

/**
 * Symfony Message sender to bridge Php-Enqueue producers.
 *
 * @author Max Kotliar <kotlyar.maksim@gmail.com>
 */
class QueueInteropSender implements SenderInterface
{
    /**
     * @var EncoderInterface
     */
    private $messageEncoder;

    /**
     * @var PsrContext
     */
    private $context;

    /**
     * @var string
     */
    private $topicName;

    /**
     * @var string
     */
    private $queueName;

    /**
     * @var float
     */
    private $deliveryDelay;

    /**
     * @var float
     */
    private $timeToLive;

    /**
     * @var int
     */
    private $priority;

    public function __construct(
        EncoderInterface $messageEncoder,
        PsrContext $context,
        string $topicName,
        string $queueName,
        float $deliveryDelay = null,
        float $timeToLive = null,
        int $priority = null
    ) {
        $this->messageEncoder = $messageEncoder;
        $this->context = $context;
        $this->topicName = $topicName;
        $this->queueName = $queueName;

        $this->deliveryDelay = $deliveryDelay;
        $this->timeToLive = $timeToLive;
        $this->priority = $priority;
    }

    /**
     * {@inheritdoc}
     */
    public function send($message)
    {
        $topic = $this->context->createTopic($this->topicName);
        if ($this->context instanceof AmqpContext) {
            $topic = $this->context->createTopic($this->topicName);
            $topic->setType(AmqpTopic::TYPE_FANOUT);
            $topic->addFlag(AmqpTopic::FLAG_DURABLE);
            $this->context->declareTopic($topic);

            $queue = $this->context->createQueue($this->queueName);
            $queue->addFlag(AmqpQueue::FLAG_DURABLE);
            $this->context->declareQueue($queue);

            $this->context->bind(new AmqpBind($queue, $topic));
        }

        $encodedMessage = $this->messageEncoder->encode($message);

        $message = $this->context->createMessage(
            $encodedMessage['body'],
            $encodedMessage['properties'] ?? [],
            $encodedMessage['headers'] ?? []
        );

        $producer = $this->context->createProducer();

        if (null !== $this->deliveryDelay) {
            $producer->setDeliveryDelay($this->deliveryDelay);
        }
        if (null !== $this->priority) {
            $producer->setPriority($this->priority);
        }
        if (null !== $this->timeToLive) {
            $producer->setTimeToLive($this->timeToLive);
        }

        try {
            $producer->send($topic, $message);
        } catch (Exception $e) {
            throw new SendingMessageFailedException($e->getMessage(), null, $e);
        }
    }
}
