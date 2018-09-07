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
use Enqueue\MessengerAdapter\Exception\MissingMessageMetadataSetterException;

class MissingMessageMetadataSetterExceptionTest extends TestCase
{
    public function testMessage()
    {
        $exception = new MissingMessageMetadataSetterException('foo', 'setFoo', 'Foo');
        $this->assertSame(
            'Missing "setFoo" setter for "foo" metadata key in "Foo" class',
            $exception->getMessage()
        );
    }
}
