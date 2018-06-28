<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Enqueue\MessengerAdapter\Exception;

use Symfony\Component\Messenger\Exception\ExceptionInterface;
use Throwable;

class RepeatMessageException extends \LogicException implements ExceptionInterface
{
    public const DEFAULT_DELAY = 1;
    public const DEFAULT_ATTEMPTS = 3;

    /** @var int delay time to sec */
    private $timeToDelay;
    /** @var int maximum number of retry attempts */
    private $maxAttempts;

    public function __construct(
        int $timeToDelay = self::DEFAULT_DELAY,
        int $maxAttempts = self::DEFAULT_ATTEMPTS,
        string $message = '',
        int $code = 0,
        Throwable $previous = null
    ) {
        parent::__construct($message, $code, $previous);
        $this->timeToDelay = $timeToDelay;
        $this->maxAttempts = $maxAttempts;
    }

    public function getTimeToDelay(): int
    {
        return $this->timeToDelay;
    }

    public function getMaxAttempts(): int
    {
        return $this->maxAttempts;
    }
}
