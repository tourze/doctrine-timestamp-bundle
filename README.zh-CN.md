# Doctrine Timestamp Bundle

[![最新版本](https://img.shields.io/packagist/v/tourze/doctrine-timestamp-bundle.svg?style=flat-square)](https://packagist.org/packages/tourze/doctrine-timestamp-bundle)
[![许可证](https://img.shields.io/badge/license-MIT-blue.svg)](LICENSE)

一个基于 Symfony 的 Bundle，通过 PHP 属性自动管理 Doctrine 实体的创建和更新时间字段。

---

## 功能特性

- 实体持久化时自动设置创建时间
- 实体更新时自动设置修改时间
- 支持 DateTime 和 Unix 时间戳两种格式
- 通过 PHP 8.1 属性进行配置
- 开箱即用：只需在实体字段上添加属性
- 兼容 Doctrine ORM 和 Symfony 6.4+

## 安装说明

### 系统要求

- PHP >= 8.1
- Symfony >= 6.4
- doctrine/doctrine-bundle >= 2.13

### Composer 安装

```bash
composer require tourze/doctrine-timestamp-bundle
```

## 快速开始

### 在实体中添加属性

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

- `Types::datetime`：以 DateTime 对象存储（默认）
- `Types::timestamp`：以 Unix 时间戳存储

无需额外配置，Bundle 会在实体持久化和更新时自动设置这些字段。

## 详细文档

### 属性说明

- `CreateTimeColumn`：标记为创建时间字段
- `UpdateTimeColumn`：标记为更新时间字段
- 均可选填 `type` 参数：`Types::datetime` 或 `Types::timestamp`

### 事件监听

- Bundle 注册了 Doctrine 事件订阅器（`TimeListener`），监听 `prePersist` 和 `preUpdate` 事件
- 实体创建时，如果字段为空，则自动设置当前时间
- 实体更新时，如果字段未被主动更改，则自动更新时间

### 高级用法

- 支持属性类型为 `DateTime` 或 `int`（时间戳）
- 通过 PropertyAccessor 灵活访问和设置实体属性

## 贡献指南

- 欢迎通过 GitHub 提交 Issue 和 PR
- 代码风格遵循 PSR-12
- 提交 PR 前请先通过测试和静态分析
- 如有 [CONTRIBUTING.md](CONTRIBUTING.md) 文件请参考

## 版权和许可

MIT 许可证，详情见 [LICENSE](LICENSE)

## 作者

tourze <https://github.com/tourze>

## 更新日志

详见 [CHANGELOG.md](CHANGELOG.md) 获取版本历史与升级说明。
