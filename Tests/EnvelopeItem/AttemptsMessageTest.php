<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Enqueue\MessengerAdapter\Tests\EnvelopeItem;

use Enqueue\MessengerAdapter\EnvelopeItem\AttemptsMessage;
use PHPUnit\Framework\TestCase;

class AttemptsMessageTest extends TestCase
{
    public function testSerialization()
    {
        $message = new AttemptsMessage(1, 3);
        $this->assertEquals(new AttemptsMessage(1, 3, 1), unserialize(serialize($message)));
    }

    public function testNowDelayToMs()
    {
        $message = new AttemptsMessage(2, 3, 2);
        $this->assertEquals(6000, $message->getNowDelayToMs());
    }

    public function getRepeatableDP(): array
    {
        return array(
            array('attempts' => 0, 'repeatable' => true),
            array('attempts' => 2, 'repeatable' => true),
            array('attempts' => 3, 'repeatable' => false),
            array('attempts' => 4, 'repeatable' => false),
        );
    }

    /**
     * @dataProvider getRepeatableDP
     */
    public function testIsRepeatable()
    {
        $message = new AttemptsMessage(1, 3, 2);
        $this->assertTrue($message->isRepeatable());

        $message = new AttemptsMessage(1, 3, 3);
        $this->assertFalse($message->isRepeatable());
    }
}
