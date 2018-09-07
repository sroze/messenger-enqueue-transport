# Enqueue's transport for Symfony Messenger component

### The project needs a maintainer. Please comment on [the issue](https://github.com/php-enqueue/messenger-adapter/issues/30) if you want to maintain this project. 

This Symfony Messenger transport allows you to use Enqueue to send and receive your messages from all the supported brokers.

## Usage

1. Install the transport

```
composer req enqueue/messenger-adapter
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
bin/console messenger:consume-messages amqp
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

### Send a message on a specific topic

You can send a message on a specific topic using `TransportConfiguration` envelope item with your message:
```php
use Symfony\Component\Messenger\Envelope;
use Enqueue\MessengerAdapter\EnvelopeItem\TransportConfiguration;

// ...

$this->bus->dispatch((new Envelope($message))->with(new TransportConfiguration(
    ['topic' => 'specific-topic']
)));
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
$this->bus->dispatch((new Envelope($message))->with(new TransportConfiguration([
    'topic' => 'topic_name',
    'metadata' => [
        'routingKey' => 'foo.bar'
    ]
])));
```
