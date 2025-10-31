# Doctrine Timestamp Bundle

[English](README.md) | [中文](README.zh-CN.md)

[![Latest Version](https://img.shields.io/packagist/v/tourze/doctrine-timestamp-bundle.svg)](https://packagist.org/packages/tourze/doctrine-timestamp-bundle)
[![PHP Version](https://img.shields.io/packagist/php-v/tourze/doctrine-timestamp-bundle.svg)](https://packagist.org/packages/tourze/doctrine-timestamp-bundle)
[![License](https://img.shields.io/badge/license-MIT-blue.svg)](LICENSE)
[![Build Status](https://img.shields.io/github/actions/workflow/status/tourze/doctrine-timestamp-bundle/ci.yml)](https://github.com/tourze/doctrine-timestamp-bundle/actions)
[![Code Coverage](https://img.shields.io/codecov/c/github/tourze/doctrine-timestamp-bundle)](https://codecov.io/gh/tourze/doctrine-timestamp-bundle)

A Symfony bundle that automatically manages creation and update timestamps for Doctrine entities via PHP attributes.

---

## Features

- Automatically sets creation timestamp on persist
- Automatically updates modification timestamp on update
- Supports both DateTime and Unix timestamp formats
- Configuration via PHP 8.1 attributes
- Zero configuration: just add attributes to your entity fields
- Compatible with Doctrine ORM and Symfony 6.4+

## Installation

```bash
composer require tourze/doctrine-timestamp-bundle
```

### Requirements

- PHP >= 8.1
- Symfony >= 6.4
- doctrine/doctrine-bundle >= 2.13

## Quick Start

### Add attributes to your entity

```php
use Tourze\DoctrineTimestampBundle\Attribute\CreateTimeColumn;
use Tourze\DoctrineTimestampBundle\Attribute\UpdateTimeColumn;
use Tourze\DoctrineTimestampBundle\Enum\Types;

class YourEntity
{
    #[CreateTimeColumn(type: Types::datetime)]
    private DateTime $createdAt;

    #[UpdateTimeColumn(type: Types::timestamp)]
    private int $updatedAt;
}
```

- `Types::datetime`: stores as DateTime object (default)
- `Types::timestamp`: stores as Unix timestamp

No further configuration is needed. The bundle will automatically set these fields during persist and update events.

## Configuration

### Bundle Registration

If using Symfony Flex, the bundle is auto-registered. Otherwise, add to `config/bundles.php`:

```php
return [
    // ...
    Tourze\DoctrineTimestampBundle\DoctrineTimestampBundle::class => ['all' => true],
];
```

### Service Configuration

The bundle automatically registers its services. No additional configuration required.

### Logging (Optional)

To enable debug logging for timestamp operations, configure your logger:

```yaml
# config/packages/monolog.yaml (dev environment)
monolog:
    handlers:
        main:
            level: debug
            channels: ["!doctrine.timestamp"]
```

## Documentation

### Attributes

- `CreateTimeColumn`: Marks a property as the creation timestamp
- `UpdateTimeColumn`: Marks a property as the update timestamp
- Both accept an optional `type` parameter: `Types::datetime` or `Types::timestamp`

### Event Subscriber

- The bundle registers a Doctrine event subscriber (`TimeListener`) that listens to `prePersist` and `preUpdate` events.
- On entity creation, if the field is empty, sets the current time.
- On entity update, if the field is not manually changed, updates the time.

### Advanced Usage

- You can use either `DateTime` or `int` (timestamp) as your property type.
- Works with property accessor for flexible entity property handling.

### Convenience Traits

The bundle provides ready-to-use traits for common scenarios:

```php
use Tourze\DoctrineTimestampBundle\Traits\CreateTimeAware;
use Tourze\DoctrineTimestampBundle\Traits\TimestampableAware;

// Only creation timestamp
class ReadOnlyEntity
{
    use CreateTimeAware;
    // Provides: $createTime property with getter/setter
}

// Both creation and update timestamps
class MutableEntity
{
    use TimestampableAware;
    // Provides: $createTime and $updateTime properties with getters/setters
    // Also includes: retrieveTimestampArray() method
}
```

These traits use `DateTimeImmutable` type and are automatically handled by the bundle.

## Security

### Timestamp Integrity

- Timestamps are set automatically by the bundle and cannot be manually overridden during normal operations
- The bundle respects existing values if manually set before persistence
- All timestamp operations are logged at debug level for audit purposes

### Best Practices

- Use `DateTimeImmutable` for better immutability guarantees
- Consider timezone implications when storing timestamps
- Validate timestamp ranges in your application logic if needed

## Contribution Guide

- Please submit issues and pull requests via GitHub.
- Code style: PSR-12
- Run tests and static analysis before submitting PRs.
- See [CONTRIBUTING.md](CONTRIBUTING.md) if available.

## License

MIT License. See [LICENSE](LICENSE) for details.

## Author

tourze <https://github.com/tourze>

## Changelog

See [CHANGELOG.md](CHANGELOG.md) for version history and upgrade notes.
