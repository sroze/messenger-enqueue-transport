# Enqueue's transport for Symfony Messenger component

**Experimental warning:** this transport is very experimental.

This Symfony Message transport allows you to use Enqueue.

## Usage

1. Install the transport

```
composer req enqueue/messenger-transport
```

2. Configure the Enqueue bundle as you would normaly do (see the Documentation). If you are using the recipes, you should
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

In the transport DSN, you can add extra configuration. Here is the reference DSN (note that the values are just for the 
example):

```
enqueue://default
    ?queue[name]=queue_name
    &topic[name]=topic_name
    &deliveryDelay=1800
    &timeToLime=3600
    &priority=1
```
