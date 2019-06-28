<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Enqueue\MessengerAdapter\EnvelopeItem;

use Interop\Queue\Message;
use Symfony\Component\Messenger\Stamp\NonSendableStampInterface;

final class InteropMessageStamp implements NonSendableStampInterface
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
