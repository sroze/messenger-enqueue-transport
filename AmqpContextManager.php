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

use Interop\Amqp\AmqpContext;
use Interop\Amqp\AmqpQueue;
use Interop\Amqp\AmqpTopic;
use Interop\Amqp\Impl\AmqpBind;
use Interop\Queue\PsrContext;

class AmqpContextManager implements ContextManager
{
    private $psrContext;

    public function __construct(PsrContext $psrContext)
    {
        $this->psrContext = $psrContext;
    }

    /**
     * {@inheritdoc}
     */
    public function psrContext(): PsrContext
    {
        return $this->psrContext;
    }

    /**
     * {@inheritdoc}
     */
    public function recoverException(\Exception $exception, array $destination): bool
    {
        if ($exception instanceof \AMQPQueueException) {
            if (404 === $exception->getCode()) {
                return $this->ensureExists($destination);
            }
        }

        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function ensureExists(array $destination): bool
    {
        if (!$this->psrContext instanceof AmqpContext) {
            return false;
        }

        $topic = $this->psrContext->createTopic($destination['topic']);
        $topic->setType(AmqpTopic::TYPE_FANOUT);
        $topic->addFlag(AmqpTopic::FLAG_DURABLE);
        $this->psrContext->declareTopic($topic);

        $queue = $this->psrContext->createQueue($destination['queue']);
        $queue->addFlag(AmqpQueue::FLAG_DURABLE);
        $this->psrContext->declareQueue($queue);

        $this->psrContext->bind(new AmqpBind($queue, $topic));

        return true;
    }
}
