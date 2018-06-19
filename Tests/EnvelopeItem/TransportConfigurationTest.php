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

use PHPUnit\Framework\TestCase;
use Enqueue\MessengerAdapter\EnvelopeItem\TransportConfiguration;

class TransportConfigurationTest extends TestCase
{
    public function testTopicConfiguration()
    {
        $transportConfiguration = new TransportConfiguration(array('topic' => 'foo'));
        $this->assertSame('foo', $transportConfiguration->getTopic());
    }

    public function testSerialization()
    {
        $transportConfiguration = new TransportConfiguration(array('topic' => 'foo'));
        $this->assertEquals($transportConfiguration, unserialize(serialize($transportConfiguration)));
    }
}
