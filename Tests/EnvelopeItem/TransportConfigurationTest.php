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

    public function testMetadataConfiguration()
    {
        $transportConfiguration = new TransportConfiguration(array('metadata' => array('foo' => 'bar')));
        $this->assertEquals(array('foo' => 'bar'), $transportConfiguration->getMetadata());
    }

    public function testDefaultConfiguration()
    {
        $transportConfiguration = new TransportConfiguration(array());
        $this->assertNull($transportConfiguration->getTopic());
        $this->assertEquals(array(), $transportConfiguration->getMetadata());
        $this->assertEquals(array(), $transportConfiguration->getOptions());
    }

    public function testSerialization()
    {
        $transportConfiguration = new TransportConfiguration(array(
            'topic' => 'foo',
            'metadata' => array('foo' => 'bar'),
            'options' => array('deliveryDelay' => 5000, 'priority' => 1),
        ));
        $this->assertEquals($transportConfiguration, unserialize(serialize($transportConfiguration)));
    }

    public function testOptionsConfiguration()
    {
        $transportConfiguration = new TransportConfiguration(array('options' => array('deliveryDelay' => 5000, 'priority' => 99)));
        $this->assertEquals(array('deliveryDelay' => '5000', 'priority' => 99), $transportConfiguration->getOptions());
    }
}
