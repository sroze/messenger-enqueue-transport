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

use Symfony\Component\Messenger\EnvelopeItemInterface;

final class RepeatMessage implements EnvelopeItemInterface
{
    /** @var int delay time to sec */
    private $timeToDelay;
    /** @var int maximum number of retry attempts */
    private $maxAttempts;
    /** @var int attempts count */
    private $attempts;

    public function __construct(int $timeToDelay, int $maxAttempts, int $attempts = 0)
    {
        $this->timeToDelay = $timeToDelay;
        $this->maxAttempts = $maxAttempts;
        $this->attempts = $attempts;
    }

    public function isRepeatable(): bool
    {
        return $this->attempts < $this->maxAttempts;
    }

    public function getNowDelayToMs(): int
    {
        return $this->timeToDelay * ($this->attempts + 1) * 1000;
    }

    public function getTimeToDelay(): int
    {
        return $this->timeToDelay;
    }

    public function getMaxAttempts(): int
    {
        return $this->maxAttempts;
    }

    public function getAttempts(): int
    {
        return $this->attempts;
    }

    public function serialize(): string
    {
        return serialize(array('timeToDelay' => $this->timeToDelay, 'attempts' => $this->attempts, 'maxAttempts' => $this->maxAttempts));
    }

    public function unserialize($serialized): void
    {
        ['timeToDelay' => $timeToDelay, 'maxAttempts' => $maxAttempts, 'attempts' => $attempts] = unserialize($serialized, array('allowed_classes' => false));
        $this->__construct($timeToDelay, $maxAttempts, $attempts + 1);
    }
}
