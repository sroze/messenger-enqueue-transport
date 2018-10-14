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
use Interop\Queue\Context;

class AmqpContextManager implements ContextManager
{
    private $context;

    public function __construct(Context $context)
    {
        $this->context = $context;
    }

    /**
     * {@inheritdoc}
     */
    public function context(): Context
    {
        return $this->context;
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
        if (!$this->context instanceof AmqpContext) {
            return false;
        }

        $topic = $this->context->createTopic($destination['topic']);
        $topic->setType(AmqpTopic::TYPE_FANOUT);
        $topic->addFlag(AmqpTopic::FLAG_DURABLE);
        $this->context->declareTopic($topic);

        $queue = $this->context->createQueue($destination['queue']);
        $queue->addFlag(AmqpQueue::FLAG_DURABLE);
        $this->context->declareQueue($queue);

        $this->context->bind(new AmqpBind($queue, $topic));

        return true;
    }
}
