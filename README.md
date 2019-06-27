# Enqueue's transport for Symfony Messenger component

This Symfony Messenger transport allows you to use Enqueue to send and receive your messages from all the supported brokers.

## Usage

1. Install the transport

```
composer req sroze/messenger-enqueue-transport
```

2. Configure the Enqueue bundle as you would normaly do ([see Enqueue's Bundle documentation](https://github.com/php-enqueue/enqueue-dev/blob/master/docs/bundle/quick_tour.md)). If you are using the recipes, you should
   just have to configure the environment variables to configure the `default` Enqueue transport:

```bash
# .env
# ...

###> enqueue/enqueue-bundle ###
ENQUEUE_DSN=amqp://guest:guest@localhost:5672/%2f
###< enqueue/enqueue-bundle ###
```

3. Configure Messenger's transport (that we will name `amqp`) to use Enqueue's `default` transport:
```yaml
# config/packages/messenger.yaml
framework:
    messenger:
        transports:
            amqp: enqueue://default
```

4. Route the messages that have to go through the message queue:
```yaml
# config/packages/framework.yaml
framework:
    messenger:
        # ...

        routing:
            'App\Message\MyMessage': amqp
```

5. Consume!

```bash
bin/console messenger:consume amqp
```

## Advanced usage

### Configure the queue(s) and exchange(s)

In the transport DSN, you can add extra configuration. Here is the common reference DSN (note that the values are just for the example):

```
enqueue://default
    ?queue[name]=queue_name
    &topic[name]=topic_name
    &deliveryDelay=1800
    &delayStrategy=Enqueue\AmqpTools\RabbitMqDelayPluginDelayStrategy
    &timeToLive=3600
    &receiveTimeout=1000
    &priority=1
```

### Setting Custom Configuration on your Message

Each Enqueue transport (e.g. amqp, redis, etc) has its own message object
that can normally be configured by calling setter methods (e.g.
`$message->setDeliveryDelay(5000)`). But in Messenger, you don't have access
to these objects directly. Instead, you can set them indirectly via
the `TransportConfiguration` stamp:

```php
use Symfony\Component\Messenger\Envelope;
use Enqueue\MessengerAdapter\EnvelopeItem\TransportConfiguration;

// ...

// create your message like normal
$message = // ...

$transportConfig = (new TransportConfiguration())
    // commmon options have a convenient method
    ->setDeliveryDelay(5000)

    // other transport-specific options are set via metadata
    // example custom option for AmqpMessage
    // each "metadata" will map to a setter on your message
    // will result in setDeliveryMode(AmqpMessage::DELIVERY_MODE_PERSISTENT)
    // being called
    ->addMetadata('deliveryMode', AmqpMessage::DELIVERY_MODE_PERSISTENT)
;

$bus->dispatch((new Envelope($message))->with($transportConfig));
```

### Send a message on a specific topic

You can send a message on a specific topic using `TransportConfiguration` envelope item with your message:

```php
use Symfony\Component\Messenger\Envelope;
use Enqueue\MessengerAdapter\EnvelopeItem\TransportConfiguration;

// ...

$transportConfig = (new TransportConfiguration())
    ->setTopic('specific-topic')
;

$bus->dispatch((new Envelope($message))->with($transportConfig));
```

### Use AMQP topic exchange

See https://www.rabbitmq.com/tutorials/tutorial-five-php.html

You can use specific topic and queue options to configure your AMQP exchange in `topic` mode and bind it:
```
enqueue://default
    ?queue[name]=queue_name
    &queue[bindingKey]=foo.#
    &topic[name]=topic_name
    &topic[type]=topic
    &deliveryDelay=1800
    &delayStrategy=Enqueue\AmqpTools\RabbitMqDelayPluginDelayStrategy
    &timeToLive=3600
    &receiveTimeout=1000
    &priority=1
```

Here is the way to send a message with a routing key matching this consumer:

```php
$bus->dispatch((new Envelope($message))->with(new TransportConfiguration([
    'topic' => 'topic_name',
    'metadata' => [
        'routingKey' => 'foo.bar'
    ]
])));
```

### Configure custom Kafka message

Here is the way to send a message with with some custom options:
```php
$this->bus->dispatch((new Envelope($message))->with(new TransportConfiguration([
    'topic' => 'test_topic_name',
    'metadata' => [
        'key' => 'foo.bar',
        'partition' => 0,
        'timestamp' => (new \DateTimeImmutable())->getTimestamp(),
        'messageId' => uniqid('kafka_', true),
    ]
])))
