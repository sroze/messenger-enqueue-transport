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

use Symfony\Component\Messenger\EnvelopeItemInterface;

/**
 * Message envelope item allowing to specify some transport configuration.
 *
 * @author Thomas Prelot <tprelot@gmail.com>
 *
 * @experimental in 4.1
 */
final class TransportConfiguration implements EnvelopeItemInterface
{
    /**
     * @param string $topic
     */
    public function __construct(array $configuration)
    {
        $this->topic = $configuration['topic'] ?? null;
    }

    /**
     * Get topic.
     *
     * @return string $topic
     */
    public function getTopic()
    {
        return $this->topic;
    }

    /**
     * Serialize object.
     */
    public function serialize()
    {
        return serialize(array(
            'topic' => $this->topic,
        ));
    }

    /**
     * Unserialize object.
     *
     * @param string $serialized
     */
    public function unserialize($serialized)
    {
        list(
            'topic' => $topic
        ) = unserialize($serialized, array('allowed_classes' => false));

        $this->__construct(array('topic' => $topic));
    }
}
