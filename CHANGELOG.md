# Change Log

The change log describes what is "Added", "Removed", "Changed" or "Fixed" between each release.

## 0.6.0

### Added

- Symfony 6 support

## 0.5.1

### Fixed

- Fixed snsqs redelivery. Make sure we redeliver to the queue and not the SNS topic.

## 0.5.0

### Changed

- Messages are rejected if the serializer fail to decode them.

## 0.4.0

### Added

- Support Symfony 5
- Support DelayStamp
- Support enqueue/enqueue-bundle: 0.10
- Support enqueue/amqp-tools: 0.10

## 0.3.2

### Fixed

- Missing `transport_name`