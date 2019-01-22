<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Enqueue\MessengerAdapter\EnvelopeItem;

use Enqueue\AmqpTools\DelayStrategy;
use Symfony\Component\Messenger\Stamp\StampInterface;

/**
 * Message envelope item allowing to specify some transport configuration.
 *
 * @author       Thomas Prelot <tprelot@gmail.com>
 *
 * @experimental in 4.1
 */
final class TransportConfiguration implements StampInterface, \Serializable
{
    private $topic;

    private $metadata = array();

    public function __construct(array $configuration = array())
    {
        $this->topic = $configuration['topic'] ?? null;
        $this->metadata = $configuration['metadata'] ?? array();
    }

    /**
     * Get topic name.
     */
    public function getTopic(): ?string
    {
        return $this->topic;
    }

    /**
     * Retrieve metadata information for decorating
     * concrete implementations of Interop\Queue\Message.
     */
    public function getMetadata(): array
    {
        return $this->metadata;
    }

    public function setTopic($topic): self
    {
        $this->topic = $topic;

        return $this;
    }

    public function setMetadata(array $metadata): self
    {
        $this->metadata = $metadata;

        return $this;
    }

    public function addMetadata(string $key, $value): self
    {
        $this->metadata[$key] = $value;

        return $this;
    }

    public function setPriority(int $priority = null): self
    {
        $this->metadata['priority'] = $priority;

        return $this;
    }

    public function setDeliveryDelay(int $deliveryDelay = null): self
    {
        $this->metadata['deliveryDelay'] = $deliveryDelay;

        return $this;
    }

    public function setDelayStrategy(DelayStrategy $delayStrategy = null): self
    {
        $this->metadata['delayStrategy'] = $delayStrategy;

        return $this;
    }

    public function setTimeToLive(int $timeToLive = null): self
    {
        $this->metadata['timeToLive'] = $timeToLive;

        return $this;
    }

    public function serialize()
    {
        return serialize(array(
            'topic' => $this->topic,
            'metadata' => $this->metadata,
        ));
    }

    public function unserialize($serialized)
    {
        list(
            'topic' => $topic,
            'metadata' => $metadata
        ) = unserialize($serialized, array('allowed_classes' => false));

        $this->__construct(array(
            'topic' => $topic,
            'metadata' => $metadata,
        ));
    }
}
