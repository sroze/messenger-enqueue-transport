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

use Interop\Queue\Exception;
use Symfony\Component\Messenger\Transport\SenderInterface;
use Symfony\Component\Messenger\Transport\Serialization\EncoderInterface;
use Enqueue\MessengerAdapter\Exception\SendingMessageFailedException;

/**
 * Symfony Message sender to bridge Php-Enqueue producers.
 *
 * @author Samuel Roze <samuel.roze@gmail.com>
 * @author Max Kotliar <kotlyar.maksim@gmail.com>
 */
class QueueInteropSender implements SenderInterface
{
    private $messageEncoder;
    private $contextManager;
    private $topicName;
    private $queueName;
    private $debug;
    private $deliveryDelay;
    private $timeToLive;
    private $priority;

    public function __construct(
        EncoderInterface $messageEncoder,
        ContextManager $contextManager,
        string $topicName,
        string $queueName,
        bool $debug = false,
        float $deliveryDelay = null,
        float $timeToLive = null,
        int $priority = null
    ) {
        $this->messageEncoder = $messageEncoder;
        $this->contextManager = $contextManager;
        $this->topicName = $topicName;
        $this->queueName = $queueName;
        $this->debug = $debug;

        $this->deliveryDelay = $deliveryDelay;
        $this->timeToLive = $timeToLive;
        $this->priority = $priority;
    }

    /**
     * {@inheritdoc}
     */
    public function send($message)
    {
        $psrContext = $this->contextManager->psrContext();
        $topic = $psrContext->createTopic($this->topicName);
        $destination = array('topic' => $this->topicName, 'queue' => $this->queueName);

        if ($this->debug) {
            $this->contextManager->ensureExists($destination);
        }

        $encodedMessage = $this->messageEncoder->encode($message);

        $psrMessage = $psrContext->createMessage(
            $encodedMessage['body'],
            $encodedMessage['properties'] ?? array(),
            $encodedMessage['headers'] ?? array()
        );

        $producer = $psrContext->createProducer();

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
            $producer->send($topic, $psrMessage);
        } catch (Exception $e) {
            if ($this->contextManager->recoverException($e, $destination)) {
                // The context manager recovered the exception, we re-try.
                return $this->send($message);
            }

            throw new SendingMessageFailedException($e->getMessage(), null, $e);
        }
    }
}
