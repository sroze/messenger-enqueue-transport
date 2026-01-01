<?php

namespace Enqueue\MessengerAdapter\EnvelopeItem;

use Interop\Queue\Message;
use Symfony\Component\Messenger\Stamp\NonSendableStampInterface;
use Symfony\Component\Messenger\Stamp\StampInterface;

class KeyStamp implements StampInterface
{
    private string $key;

    public function getKey(): ?string
    {
        return $this->key;
    }

    public function setKey(string $key = null): self
    {
        $this->key = $key;

        return $this;
    }
}
