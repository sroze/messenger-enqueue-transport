<?php

namespace Enqueue\MessengerAdapter;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Messenger\Transport\TransportFactoryInterface;
use Symfony\Component\Messenger\Transport\TransportInterface;
use Symfony\Component\Messenger\Transport\ReceiverInterface;
use Symfony\Component\Messenger\Transport\SenderInterface;
use Symfony\Component\Messenger\Transport\Serialization\EncoderInterface;
use Symfony\Component\Messenger\Transport\Serialization\DecoderInterface;

/**
 * Symfony Messenger transport factory.
 *
 * @author Samuel Roze <samuel.roze@gmail.com>
 */
class QueueInteropTransportFactory implements TransportFactoryInterface
{
    private $decoder;
    private $encoder;
    private $debug;

    private $container;

    public function __construct(DecoderInterface $decoder, EncoderInterface $encoder, ContainerInterface $container, bool $debug = false)
    {
        $this->encoder = $encoder;
        $this->decoder = $decoder;
        $this->container = $container;
        $this->debug = $debug;
    }

    // BC layer for Symfony 4.1 beta1
    public function createReceiver(string $dsn, array $options): ReceiverInterface
    {
        return $this->createTransport($dsn, $options);
    }

    // BC layer for Symfony 4.1 beta1
    public function createSender(string $dsn, array $options): SenderInterface
    {
        return $this->createTransport($dsn, $options);
    }

    public function createTransport(string $dsn, array $options): TransportInterface
    {
        list($contextManager, $options) = $this->parseDsn($dsn);

        return new QueueInteropTransport(
            $this->decoder,
            $this->encoder,
            $contextManager,
            $options,
            $this->debug
        );
    }

    public function supports(string $dsn, array $options): bool
    {
        return 0 === strpos($dsn, 'enqueue://');
    }

    private function parseDsn(string $dsn)
    {
        $parsedDsn = parse_url($dsn);
        $enqueueContextName = $parsedDsn['host'];

        $amqpOptions = [];
        if (isset($parsedUrl['query'])) {
            parse_str($parsedUrl['query'], $parsedQuery);

            $amqpOptions = array_replace_recursive($amqpOptions, $parsedQuery);
        }

        if (!$this->container->has($contextService = 'enqueue.transport.'.$enqueueContextName.'.context')) {
            throw new \RuntimeException(sprintf(
                'Can\'t find Enqueue\'s transport named "%s": Service "%s" is not found.',
                $enqueueContextName,
                $contextService
            ));
        }

        return [
            new AmqpContextManager(
                $this->container->get($contextService)
            ),
            $amqpOptions
        ];
    }
}
