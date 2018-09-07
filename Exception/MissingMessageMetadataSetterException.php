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

class MissingMessageMetadataSetterException extends \LogicException implements ExceptionInterface
{
    public function __construct(string $metadata, string $setter, string $class)
    {
        parent::__construct(sprintf(
            'Missing "%s" setter for "%s" metadata key in "%s" class',
            $setter,
            $metadata,
            $class
        ));
    }
}
