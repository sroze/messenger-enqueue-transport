<?php

namespace Enqueue\MessengerAdapter\Tests\Model;

use Enqueue\MessengerAdapter\Model\MessageAttributes;
use PHPUnit\Framework\TestCase;

class MessageAttributesTest extends TestCase
{
    public function testToArray()
    {
        $messageAttributes = new MessageAttributes(array('attr1' => 'value1'));
        $this->assertSame(
            array(
                'attr1' => array(
                    'DataType' => 'String',
                    'StringValue' => 'value1',
                ),
            ),
            $messageAttributes->toArray()
        );
    }

    public function testMerge()
    {
        $this->assertEquals(
            new MessageAttributes(array('attr1' => 'value1', 'attr2' => 'value2')),
            MessageAttributes::merge(
                new MessageAttributes(array('attr1' => 'value1')),
                new MessageAttributes(array('attr2' => 'value2'))
            )
        );
    }

    public function testThrowExceptionForInvalidAttribute()
    {
        $this->expectException(\InvalidArgumentException::class);
        new MessageAttributes(array('invalidKey****' => 'value1'));
    }
}
