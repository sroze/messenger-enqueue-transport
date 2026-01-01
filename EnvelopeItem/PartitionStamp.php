<?php

namespace Enqueue\MessengerAdapter\EnvelopeItem;

use Interop\Queue\Message;
use Symfony\Component\Messenger\Stamp\NonSendableStampInterface;
use Symfony\Component\Messenger\Stamp\StampInterface;

class PartitionStamp implements StampInterface
{
    private int $partition;

    public function getPartition(): int
    {
        return $this->partition;
    }

    public function setPartition(int $partition): self
    {
        $this->partition = $partition;
        
        return $this;
    }
}
