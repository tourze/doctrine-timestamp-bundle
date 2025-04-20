# Doctrine Timestamp Bundle

[![Latest Version](https://img.shields.io/packagist/v/tourze/doctrine-timestamp-bundle.svg?style=flat-square)](https://packagist.org/packages/tourze/doctrine-timestamp-bundle)
[![License](https://img.shields.io/badge/license-MIT-blue.svg)](LICENSE)

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

### Requirements

- PHP >= 8.1
- Symfony >= 6.4
- doctrine/doctrine-bundle >= 2.13

### Install via Composer

```bash
composer require tourze/doctrine-timestamp-bundle
```

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
