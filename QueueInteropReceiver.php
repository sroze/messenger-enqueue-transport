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

use Enqueue\MessengerAdapter\Exception\RejectMessageException;
use Enqueue\MessengerAdapter\Exception\RequeueMessageException;
use Symfony\Component\Messenger\Transport\ReceiverInterface;
use Symfony\Component\Messenger\Transport\Serialization\DecoderInterface;

/**
 * Symfony Message receivers to get messages from php-enqueue consumers.
 *
 * @author Samuel Roze <samuel.roze@gmail.com>
 * @author Max Kotliar <kotlyar.maksim@gmail.com>
 */
class QueueInteropReceiver implements ReceiverInterface
{
    private $messageDecoder;
    private $contextManager;
    private $queueName;
    private $topicName;
    private $receiveTimeout;
    private $debug;
    private $shouldStop;

    public function __construct(DecoderInterface $messageDecoder, ContextManager $contextManager, string $queueName, string $topicName, $debug = false)
    {
        $this->messageDecoder = $messageDecoder;
        $this->contextManager = $contextManager;
        $this->queueName = $queueName;
        $this->topicName = $topicName;
        $this->debug = $debug;

        $this->receiveTimeout = 1000; // 1s
    }

    /**
     * {@inheritdoc}
     */
    public function receive(callable $handler): void
    {
        $psrContext = $this->contextManager->psrContext();
        $queue = $psrContext->createQueue($this->queueName);
        $consumer = $psrContext->createConsumer($queue);
        $destination = array('topic' => $this->topicName, 'queue' => $this->queueName);

        if ($this->debug) {
            $this->contextManager->ensureExists($destination);
        }

        while (!$this->shouldStop) {
            try {
                if (null === ($message = $consumer->receive($this->receiveTimeout))) {
                    continue;
                }
            } catch (\Exception $e) {
                if ($this->contextManager->recoverException($e, $destination)) {
                    continue;
                }

                throw $e;
            }

            try {
                $handler($this->messageDecoder->decode(array(
                    'body' => $message->getBody(),
                    'headers' => $message->getHeaders(),
                    'properties' => $message->getProperties(),
                )));

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

    public function stop(): void
    {
        $this->shouldStop = true;
    }
}
