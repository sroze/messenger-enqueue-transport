# Enqueue bridge for Symfony Message component

This bridge will allow you to use [Php-Enqueue](https://github.com/php-enqueue/enqueue-dev)'s great list of brokers for your messages that are going through Symfony's Message component.

## Usage

1. Install the enqueue bridge and the enqueue AMQP extension. (Note that you can use any of the multiple php-enqueue extensions)

```
composer req sroze/enqueue-bridge:dev-master enqueue/amqp-ext
```

2. Configure the adapter.
```yaml
# config/packages/enqueue.yaml
enqueue:
    transport:
        default: 'amqp'
        amqp:
            host: 'localhost'
            port: 5672
            user: 'guest'
            pass: 'guest'
            vhost: '/'
            receive_method: basic_consume
    client: ~
```

3. Register your consumer & producer
```yaml
# config/services.yaml
services:
    app.amqp_consumer:
        class: Sam\Symfony\Bridge\EnqueueMessage\EnqueueConsumer
        arguments:
        - "@message.transport.default_decoder"
        - "@enqueue.transport.amqp.context"
        - "messages" # Name of the queue

    app.amqp_producer:
        class: Sam\Symfony\Bridge\EnqueueMessage\EnqueueProducer
        arguments: 
        - "@message.transport.default_encoder"
        - "@enqueue.transport.amqp.context"
        - "messages" # Name of the queue
```

4. Route your messages to the consumer
```yaml
# config/packages/framework.yaml
framework:
    message:
        routing:
            'App\Message\MyMessage': app.amqp_producer
```

You are done. The `MyMessage` messages will be sent to your local RabbitMq instance. In order to process
them asynchronously, you need to consume the messages pushed in the queue. You can start a worker with the `message:consume`
command:

```bash
bin/console message:consume --consumer=app.amqp_consumer
```

