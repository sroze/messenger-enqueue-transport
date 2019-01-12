<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Enqueue\MessengerAdapter;

use Enqueue\AmqpTools\DelayStrategyAware;
use Enqueue\AmqpTools\RabbitMqDelayPluginDelayStrategy;
use Enqueue\AmqpTools\RabbitMqDlxDelayStrategy;
use Enqueue\MessengerAdapter\Exception\RejectMessageException;
use Enqueue\MessengerAdapter\Exception\RequeueMessageException;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\Transport\Serialization\SerializerInterface;
use Symfony\Component\Messenger\Transport\TransportInterface;
use Interop\Queue\Exception as InteropQueueException;
use Interop\Queue\Message;
use Enqueue\MessengerAdapter\Exception\MissingMessageMetadataSetterException;
use Enqueue\MessengerAdapter\Exception\SendingMessageFailedException;
use Enqueue\MessengerAdapter\EnvelopeItem\TransportConfiguration;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Symfony Messenger transport.
 *
 * @author Samuel Roze <samuel.roze@gmail.com>
 * @author Max Kotliar <kotlyar.maksim@gmail.com>
 */
class QueueInteropTransport implements TransportInterface
{
    private $serializer;
    private $contextManager;
    private $options;
    private $debug;
    private $shouldStop;

    public function __construct(
        SerializerInterface $serializer,
        ContextManager $contextManager,
        array $options = array(),
        $debug = false
    ) {
        $this->serializer = $serializer;
        $this->contextManager = $contextManager;
        $this->debug = $debug;

        $resolver = new OptionsResolver();
        $this->configureOptions($resolver);
        $this->options = $resolver->resolve($options);
    }

    /**
     * {@inheritdoc}
     */
    public function receive(callable $handler): void
    {
        $context = $this->contextManager->context();
        $destination = $this->getDestination(null);
        $queue = $context->createQueue($destination['queue']);
        $consumer = $context->createConsumer($queue);

        if ($this->debug) {
            $this->contextManager->ensureExists($destination);
        }

        while (!$this->shouldStop) {
            try {
                if (null === ($interopMessage = $consumer->receive($this->options['receiveTimeout'] ?? 30000))) {
                    $handler(null);
                    continue;
                }
            } catch (\Exception $e) {
                if ($this->contextManager->recoverException($e, $destination)) {
                    continue;
                }

                throw $e;
            }

            try {
                $handler($this->serializer->decode(array(
                    'body' => $interopMessage->getBody(),
                    'headers' => $interopMessage->getHeaders(),
                    'properties' => $interopMessage->getProperties(),
                )));

                $consumer->acknowledge($interopMessage);
            } catch (RejectMessageException $e) {
                $consumer->reject($interopMessage);
            } catch (RequeueMessageException $e) {
                $consumer->reject($interopMessage, true);
            }
        }
    }

    /**
     * {@inheritdoc}
     */
    public function send(Envelope $envelope): Envelope
    {
        $context = $this->contextManager->context();
        $destination = $this->getDestination($envelope);
        $topic = $context->createTopic($destination['topic']);

        if ($this->debug) {
            $this->contextManager->ensureExists($destination);
        }

        $encodedMessage = $this->serializer->encode($envelope);

        $interopMessage = $context->createMessage(
            $encodedMessage['body'],
            $encodedMessage['properties'] ?? array(),
            $encodedMessage['headers'] ?? array()
        );

        $this->setMessageMetadata($interopMessage, $envelope);

        $producer = $context->createProducer();

        if (isset($this->options['deliveryDelay'])) {
            if ($producer instanceof DelayStrategyAware) {
                $producer->setDelayStrategy($this->options['delayStrategy']);
            }
            $producer->setDeliveryDelay($this->options['deliveryDelay']);
        }
        if (isset($this->options['priority'])) {
            $producer->setPriority($this->options['priority']);
        }
        if (isset($this->options['timeToLive'])) {
            $producer->setTimeToLive($this->options['timeToLive']);
        }

        try {
            $producer->send($topic, $interopMessage);
        } catch (InteropQueueException $e) {
            if (!$this->contextManager->recoverException($e, $destination)) {
                throw new SendingMessageFailedException($e->getMessage(), null, $e);
            }
            
            // The context manager recovered the exception, we re-try.
            $envelope = $this->send($envelope);
        }

        return $envelope;
    }

    /**
     * {@inheritdoc}
     */
    public function stop(): void
    {
        $this->shouldStop = true;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(array(
            'receiveTimeout' => null,
            'deliveryDelay' => null,
            'delayStrategy' => RabbitMqDelayPluginDelayStrategy::class,
            'priority' => null,
            'timeToLive' => null,
            'topic' => array('name' => 'messages'),
            'queue' => array('name' => 'messages'),
        ));

        $resolver->setAllowedTypes('receiveTimeout', array('null', 'int'));
        $resolver->setAllowedTypes('deliveryDelay', array('null', 'int'));
        $resolver->setAllowedTypes('priority', array('null', 'int'));
        $resolver->setAllowedTypes('timeToLive', array('null', 'int'));
        $resolver->setAllowedTypes('delayStrategy', array('null', 'string'));

        $resolver->setAllowedValues('delayStrategy', array(
                null,
                RabbitMqDelayPluginDelayStrategy::class,
                RabbitMqDlxDelayStrategy::class,
            )
        );

        $resolver->setNormalizer('delayStrategy', function (Options $options, $value) {
            return null !== $value ? new $value() : null;
        });
    }

    private function getDestination(?Envelope $envelope): array
    {
        $configuration = $envelope ? $envelope->last(TransportConfiguration::class) : null;
        $topic = null !== $configuration ? $configuration->getTopic() : null;

        return array(
            'topic' => $topic ?? $this->options['topic']['name'],
            'topicOptions' => $this->options['topic'],
            'queue' => $this->options['queue']['name'],
            'queueOptions' => $this->options['queue'],
        );
    }

    private function setMessageMetadata(Message $interopMessage, Envelope $envelope): void
    {
        $configuration = $envelope->last(TransportConfiguration::class);

        if (null === $configuration) {
            return;
        }

        $metadata = $configuration->getMetadata();
        $class = new \ReflectionClass($interopMessage);

        foreach ($metadata as $key => $value) {
            $setter = sprintf('set%s', ucfirst($key));
            if (!$class->hasMethod($setter)) {
                throw new MissingMessageMetadataSetterException($key, $setter, $class->getName());
            }
            $interopMessage->{$setter}($value);
        }
    }
}
