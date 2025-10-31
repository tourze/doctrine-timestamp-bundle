<?php

declare(strict_types=1);

namespace Tourze\DoctrineTimestampBundle\Tests\Traits;

use Tourze\DoctrineTimestampBundle\Traits\CreateTimeAware;

/**
 * 使用 CreateTimeAware trait 的测试实体类
 *
 * @internal
 */
class TestEntityWithCreateTime
{
    use CreateTimeAware;
}
