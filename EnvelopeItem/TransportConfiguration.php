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
    /**
     * @var string
     */
    private $topic;

    /**
     * @var array
     */
    private $metadata;

    /**
     * @var array
     */
    private $options;

    /**
     * @param array $configuration
     */
    public function __construct(array $configuration)
    {
        $this->topic = $configuration['topic'] ?? null;
        $this->metadata = $configuration['metadata'] ?? array();
        $this->options = $configuration['options'] ?? array();
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

    /**
     * Retrieve producer options.
     *
     * @return array
     */
    public function getOptions(): array
    {
        return $this->options;
    }

    /**
     * Serialize object.
     */
    public function serialize()
    {
        return serialize(array(
            'topic' => $this->topic,
            'metadata' => $this->metadata,
            'options' => $this->options,
        ));
    }

    /**
     * Unserialize object.
     */
    public function unserialize($serialized)
    {
        list(
            'topic' => $topic,
            'metadata' => $metadata,
            'options' => $options
        ) = unserialize($serialized, array('allowed_classes' => false));

        $this->__construct(array(
            'topic' => $topic,
            'metadata' => $metadata,
            'options' => $options,
        ));
    }
}
