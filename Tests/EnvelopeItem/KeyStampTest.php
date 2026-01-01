<?php

namespace Enqueue\MessengerAdapter\Tests\EnvelopeItem;

use Enqueue\MessengerAdapter\EnvelopeItem\KeyStamp;
use PHPUnit\Framework\TestCase;

class KeyStampTest extends TestCase
{
    public function testPartitionSetter()
    {
        $keyStamp = (new KeyStamp())->setKey('key');
        $this->assertInstanceOf(KeyStamp::class, $keyStamp);
    }

    public function testPartitionGetter()
    {
        $keyStamp = (new KeyStamp())->setKey('key');
        $this->assertEquals('key', $keyStamp->getKey());
    }
}
