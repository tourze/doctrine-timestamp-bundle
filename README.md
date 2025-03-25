# Doctrine Timestamp Bundle

A Symfony bundle that automatically handles timestamp fields in Doctrine entities.

[English](#english) | [中文](#中文)

## English

### Installation

```bash
composer require tourze/doctrine-timestamp-bundle
```

### Features

- Automatically sets creation time when an entity is created
- Automatically updates modification time when an entity is updated
- Supports both DateTime and timestamp formats
- Uses PHP 8.1 attributes for configuration
- No configuration needed, just add attributes to your entity properties

### Usage

Add attributes to your entity properties:

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

### Available Types

- `Types::datetime` - Stores as DateTime object (default)
- `Types::timestamp` - Stores as Unix timestamp

### Requirements

- PHP 8.1 or higher
- Symfony 6.4 or higher
- Doctrine Bundle 2.13 or higher

## 中文

### 安装

```bash
composer require tourze/doctrine-timestamp-bundle
```

### 功能特点

- 自动设置实体创建时间
- 自动更新实体修改时间
- 支持 DateTime 和时间戳两种格式
- 使用 PHP 8.1 属性进行配置
- 无需额外配置，只需在实体属性上添加属性即可

### 使用方法

在实体属性上添加属性：

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

### 支持的类型

- `Types::datetime` - 存储为 DateTime 对象（默认）
- `Types::timestamp` - 存储为 Unix 时间戳

### 系统要求

- PHP 8.1 或更高版本
- Symfony 6.4 或更高版本
- Doctrine Bundle 2.13 或更高版本
