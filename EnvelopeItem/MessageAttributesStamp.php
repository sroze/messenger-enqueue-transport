<?php

namespace Enqueue\MessengerAdapter\EnvelopeItem;

use Enqueue\MessengerAdapter\Model\MessageAttributes;
use Interop\Queue\Message;
use Symfony\Component\Messenger\Stamp\NonSendableStampInterface;

final class MessageAttributesStamp implements NonSendableStampInterface
{
    /** @var MessageAttributes */
    private $attributes;

    public function __construct(MessageAttributes $attributes)
    {
        $this->attributes = $attributes;
    }

    public function getAttributes(): MessageAttributes
    {
        return $this->attributes;
    }
}
