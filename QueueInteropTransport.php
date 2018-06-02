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
use Symfony\Component\Messenger\Transport\Serialization\DecoderInterface;
use Symfony\Component\Messenger\Transport\Serialization\EncoderInterface;
use Symfony\Component\Messenger\Transport\TransportInterface;
use Interop\Queue\Exception as InteropQueueException;
use Enqueue\MessengerAdapter\Exception\SendingMessageFailedException;
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
    private $decoder;
    private $encoder;
    private $contextManager;
    private $options;
    private $debug;
    private $shouldStop;

    public function __construct(DecoderInterface $decoder, EncoderInterface $encoder, ContextManager $contextManager, array $options = array(), $debug = false)
    {
        $this->decoder = $decoder;
        $this->encoder = $encoder;
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
        $psrContext = $this->contextManager->psrContext();
        $destination = $this->getDestination();
        $queue = $psrContext->createQueue($destination['queue']);
        $consumer = $psrContext->createConsumer($queue);

        if ($this->debug) {
            $this->contextManager->ensureExists($destination);
        }

        while (!$this->shouldStop) {
            try {
                if (null === ($message = $consumer->receive($this->options['receiveTimeout'] ?? 0))) {
                    continue;
                }
            } catch (\Exception $e) {
                if ($this->contextManager->recoverException($e, $destination)) {
                    continue;
                }

                throw $e;
            }

            try {
                $handler($this->decoder->decode(array(
                    'body' => $message->getBody(),
                    'headers' => $message->getHeaders(),
                    'properties' => $message->getProperties(),
                )));

                $consumer->acknowledge($message);
            } catch (RejectMessageException $e) {
                $consumer->reject($message);
            } catch (RequeueMessageException $e) {
                $consumer->reject($message, true);
            } catch (\Throwable $e) {
                $consumer->reject($message);
            }
        }
    }

    /**
     * {@inheritdoc}
     */
    public function send(Envelope $message): void
    {
        $psrContext = $this->contextManager->psrContext();
        $destination = $this->getDestination();
        $topic = $psrContext->createTopic($destination['topic']);

        if ($this->debug) {
            $this->contextManager->ensureExists($destination);
        }

        $encodedMessage = $this->encoder->encode($message);

        $psrMessage = $psrContext->createMessage(
            $encodedMessage['body'],
            $encodedMessage['properties'] ?? array(),
            $encodedMessage['headers'] ?? array()
        );

        $producer = $psrContext->createProducer();

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
            $producer->send($topic, $psrMessage);
        } catch (InteropQueueException $e) {
            if ($this->contextManager->recoverException($e, $destination)) {
                // The context manager recovered the exception, we re-try.
                $this->send($message);

                return;
            }

            throw new SendingMessageFailedException($e->getMessage(), null, $e);
        }
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

        $resolver->setAllowedValues('delayStrategy', array(null, RabbitMqDelayPluginDelayStrategy::class, RabbitMqDlxDelayStrategy::class));
        $resolver->setNormalizer('delayStrategy', function (Options $options, $value) {
            return null !== $value ? new $value() : null;
        });
    }

    private function getDestination(): array
    {
        return array(
            'topic' => $this->options['topic']['name'],
            'queue' => $this->options['queue']['name'],
        );
    }
}
