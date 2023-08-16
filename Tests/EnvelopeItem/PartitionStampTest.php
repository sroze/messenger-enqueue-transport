<?php

namespace Enqueue\MessengerAdapter\Tests\EnvelopeItem;

use Enqueue\MessengerAdapter\EnvelopeItem\PartitionStamp;
use PHPUnit\Framework\TestCase;

class PartitionStampTest extends TestCase
{
    public function testPartitionSetter()
    {
        $partitionStamp = (new PartitionStamp())->setPartition(1);
        $this->assertInstanceOf(PartitionStamp::class, $partitionStamp);
    }

    public function testPartitionGetter()
    {
        $partitionStamp = (new PartitionStamp())->setPartition(1);
        $this->assertEquals(1, $partitionStamp->getPartition());
    }
}
