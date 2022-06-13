<?php

namespace Enqueue\MessengerAdapter\Model;

final class MessageAttributes
{
    /** @var array<string, string> */
    private $attributes;

    /** @param array<string, string> $attributes */
    public function __construct(array $attributes)
    {
        if (empty($attributes)) {
            throw new \InvalidArgumentException('MessageAttributes should have at least one attribute');
        }
        \array_walk($attributes, function (string $value, string $name) {
            return $this->add($name, $value);
        });
    }

    /** @return array<string, array<string, string>> */
    public function toArray(): array
    {
        $result = array();
        foreach ($this->attributes as $name => $value) {
            $result[$name] = array(
                'DataType' => 'String',
                'StringValue' => $value,
            );
        }

        return $result;
    }

    public static function merge(self ...$messageAttributes): self
    {
        return new self(
            \array_merge(
                ...\array_map(static function (MessageAttributes $attributes) {
                    return $attributes->attributes;
                }, $messageAttributes)
            )
        );
    }

    private function add(string $name, string $value): void
    {
        if (0 === \preg_match('/^[A-Za-z0-9-_.]{1,256}$/', $name)) {
            throw new \InvalidArgumentException(
                \sprintf(
                    '"%s" is invalid massage attribute name. See details here: %s',
                    $name,
                    'https://docs.aws.amazon.com/sns/latest/dg/sns-message-attributes.html'
                )
            );
        }

        $this->attributes[$name] = $value;
    }
}
