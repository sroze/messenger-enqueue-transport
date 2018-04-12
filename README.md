# Enqueue's adapter for Symfony Messenger component

**Experimental warning:** this adapter is very experimental.

This adapter allows you to use Enqueue with the Symfony Messenger component.

## Usage

1. Install the adapter

```
composer req enqueue/messenger-adapter
```

2. Configure your adapters and routing:
```yaml
# config/packages/framework.yaml
framework:
    messenger:
        adapters:
            my_queue: enqueue+sqs:?key=[your-key]&secret=[your-secret]&region=[your-region]

        routing:
            'App\Message\MyMessage': my_queue
```

3. Consume!

```bash
bin/console messenger:consume-messages my_queue
```
