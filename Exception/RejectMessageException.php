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

class RejectMessageException extends \LogicException implements ExceptionInterface
{
}
