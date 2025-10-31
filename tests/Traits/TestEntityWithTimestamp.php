<?php

declare(strict_types=1);

namespace Tourze\DoctrineTimestampBundle\Tests\Traits;

use Tourze\DoctrineTimestampBundle\Traits\TimestampableAware;

/**
 * 使用 TimestampableAware trait 的测试实体类
 *
 * @internal
 */
class TestEntityWithTimestamp
{
    use TimestampableAware;
}
