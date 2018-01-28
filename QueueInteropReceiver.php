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
use Interop\Queue\PsrContext;
use Symfony\Component\Message\Transport\ReceiverInterface;
use Symfony\Component\Message\Transport\Serialization\DecoderInterface;

/**
 * Symfony Message receivers to get messages from php-enqueue consumers.
 *
 * @author Max Kotliar <kotlyar.maksim@gmail.com>
 */
class QueueInteropReceiver implements ReceiverInterface
{
    /**
     * @var DecoderInterface
     */
    private $messageDecoder;

    /**
     * @var PsrContext
     */
    private $context;

    /**
     * @var string
     */
    private $queueName;

    /**
     * @var string
     */
    private $topicName;

    /**
     * @var float
     */
    private $receiveTimeout;

    public function __construct(DecoderInterface $messageDecoder, PsrContext $context, string $queueName, string $topicName)
    {
        $this->messageDecoder = $messageDecoder;
        $this->context = $context;
        $this->queueName = $queueName;
        $this->topicName = $topicName;

        $this->receiveTimeout = 1000; // 1s
    }

    /**
     * {@inheritdoc}
     */
    public function receive(): iterable
    {
        $queue = $this->context->createQueue($this->queueName);
        $consumer = $this->context->createConsumer($queue);

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

        while (true) {
            if (null === ($message = $consumer->receive($this->receiveTimeout))) {
                continue;
            }

            try {
                yield $this->messageDecoder->decode([
                    'body' => $message->getBody(),
                    'headers' => $message->getHeaders(),
                    'properties' => $message->getProperties(),
                ]);

                $consumer->acknowledge($message);
            } catch (RejectMessageException $e) {
                $consumer->reject($message);
            } catch (RequeueMessageException $e) {
                $consumer->reject($message, true);
            } catch (\Throwable $e) {
                $consumer->reject($message);
            }
        }
    }
}
