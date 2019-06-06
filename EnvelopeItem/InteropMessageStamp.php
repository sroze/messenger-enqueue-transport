<?php

namespace Enqueue\MessengerAdapter\EnvelopeItem;

use Interop\Queue\Message;
use Symfony\Component\Messenger\Stamp\StampInterface;

final class InteropMessageStamp implements StampInterface
{
    /** @var Message */
    private $message;

    public function __construct(Message $message)
    {
        $this->message = $message;
    }

    public function getMessage(): Message
    {
        return $this->message;
    }
}
