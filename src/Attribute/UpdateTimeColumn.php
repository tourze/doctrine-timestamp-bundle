<?php

namespace Tourze\DoctrineTimestampBundle\Attribute;

use Tourze\DoctrineTimestampBundle\Enum\Types;

/**
 * 记录更新时间
 */
#[\Attribute(\Attribute::TARGET_PROPERTY)]
class UpdateTimeColumn
{
    public function __construct(
        public Types $type = Types::datetime
    ) {
    }
}
