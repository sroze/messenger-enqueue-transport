<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Enqueue\MessengerAdapter\Tests\Fixtures;

use Interop\Queue\Message;

class DecoratedPsrMessage implements Message
{
    /**
     * @var string
     */
    private $body;

    /**
     * @var array
     */
    private $properties;

    /**
     * @var array
     */
    private $headers;

    /**
     * @var string|null
     */
    private $deliveryTag;

    /**
     * @var string|null
     */
    private $consumerTag;

    /**
     * @var bool
     */
    private $redelivered;

    /**
     * @var int
     */
    private $flags;

    /**
     * @var string
     */
    private $routingKey;

    /**
     * @param string $body
     */
    public function __construct($body = '', array $properties = array(), array $headers = array())
    {
        $this->body = $body;
        $this->properties = $properties;
        $this->headers = $headers;

        $this->redelivered = false;
        $this->flags = self::FLAG_NOPARAM;
    }

    /**
     * {@inheritdoc}
     */
    public function getBody(): string
    {
        return $this->body;
    }

    /**
     * {@inheritdoc}
     */
    public function setBody(string $body): void
    {
        $this->body = $body;
    }

    /**
     * {@inheritdoc}
     */
    public function setProperties(array $properties): void
    {
        $this->properties = $properties;
    }

    /**
     * {@inheritdoc}
     */
    public function getProperties(): array
    {
        return $this->properties;
    }

    /**
     * {@inheritdoc}
     */
    public function setProperty(string $name, $value): void
    {
        $this->properties[$name] = $value;
    }

    /**
     * {@inheritdoc}
     */
    public function getProperty(string $name, $default = null)
    {
        return \array_key_exists($name, $this->properties) ? $this->properties[$name] : $default;
    }

    /**
     * {@inheritdoc}
     */
    public function setHeaders(array $headers): void
    {
        $this->headers = $headers;
    }

    /**
     * {@inheritdoc}
     */
    public function getHeaders(): array
    {
        return $this->headers;
    }

    /**
     * {@inheritdoc}
     */
    public function setHeader(string $name, $value): void
    {
        $this->headers[$name] = $value;
    }

    /**
     * {@inheritdoc}
     */
    public function getHeader(string $name, $default = null)
    {
        return \array_key_exists($name, $this->headers) ? $this->headers[$name] : $default;
    }

    /**
     * {@inheritdoc}
     */
    public function setRedelivered(bool $redelivered): void
    {
        $this->redelivered = (bool) $redelivered;
    }

    /**
     * {@inheritdoc}
     */
    public function isRedelivered(): bool
    {
        return $this->redelivered;
    }

    /**
     * {@inheritdoc}
     */
    public function setCorrelationId(?string $correlationId = null): void
    {
        $this->setHeader('correlation_id', $correlationId);
    }

    /**
     * {@inheritdoc}
     */
    public function getCorrelationId(): ?string
    {
        return $this->getHeader('correlation_id');
    }

    /**
     * {@inheritdoc}
     */
    public function setMessageId(?string $messageId = null): void
    {
        $this->setHeader('message_id', $messageId);
    }

    /**
     * {@inheritdoc}
     */
    public function getMessageId(): ?string
    {
        return $this->getHeader('message_id');
    }

    /**
     * {@inheritdoc}
     */
    public function getTimestamp(): ?int
    {
        $value = $this->getHeader('timestamp');

        return null === $value ? null : (int) $value;
    }

    /**
     * {@inheritdoc}
     */
    public function setTimestamp(?int $timestamp = null): void
    {
        $this->setHeader('timestamp', $timestamp);
    }

    /**
     * {@inheritdoc}
     */
    public function setReplyTo(?string $replyTo = null): void
    {
        $this->setHeader('reply_to', $replyTo);
    }

    /**
     * {@inheritdoc}
     */
    public function getReplyTo(): ?string
    {
        return $this->getHeader('reply_to');
    }

    /**
     * {@inheritdoc}
     */
    public function setRoutingKey(?string $routingKey = null): void
    {
        $this->routingKey = $routingKey;
    }

    /**
     * {@inheritdoc}
     */
    public function getRoutingKey(): ?string
    {
        return $this->routingKey;
    }
}
